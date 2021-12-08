<?php

declare(strict_types=1);

namespace Phpcq\Runner\Report\Writer;

use Generator;
use Phpcq\PluginApi\Version10\Report\DiagnosticBuilderInterface;
use Phpcq\PluginApi\Version10\Report\ReportInterface;
use Phpcq\PluginApi\Version10\Report\TaskReportInterface;
use Phpcq\Runner\Exception\RuntimeException;
use Phpcq\Runner\Report\Buffer\FileRangeBuffer;
use Phpcq\Runner\Report\Buffer\ReportBuffer;
use Symfony\Component\Filesystem\Filesystem;

use function count;

/**
 * @psalm-type TCodeClimateLocationTypeA=array{
 *   path: string,
 *   lines: array{
 *     begin: int,
 *     end: int
 *   }
 * }
 * @psalm-type TCodeClimateLocationTypeB=array{
 *   path: string,
 *   positions: array{
 *     begin: array{
 *       line: int,
 *       column: int
 *     },
 *     end: array{
 *       line: int,
 *       column: int
 *     }
 *   }
 * }
 * @psalm-type TCodeClimateLocation=TCodeClimateLocationTypeA|TCodeClimateLocationTypeB
 * @psalm-type TCodeClimateLocationList=list<TCodeClimateLocation>
 * @psalm-type TCodeClimateIssueCategory='Bug Risk'|'Clarity'|'Compatibility'|'Complexity'|'Duplication'|'Performance'
 *                                       |'Security'|'Style'
 * @psalm-type TCodeClimateIssueSeverity='info'|'minor'|'major'|'critical'|'blocker'
 * @psalm-type TCodeClimateIssue=array{
 *   type: 'issue',
 *   check_name: string,
 *   description: string,
 *   content?: string,
 *   categories: list<TCodeClimateIssueCategory>,
 *   location: TCodeClimateLocation,
 *   other_locations?: TCodeClimateLocationList,
 *   remediation_points?: int,
 *   severity?: TCodeClimateIssueSeverity,
 *   fingerprint?: string
 * }
 *
 */
final class CodeClimateReportWriter
{
    public static function writeReport(string $targetPath, ReportBuffer $report, string $minimumSeverity): void
    {
        if ($report->getStatus() === ReportInterface::STATUS_STARTED) {
            throw new RuntimeException('Only completed reports may be saved');
        }

        $filesystem  = new Filesystem();
        $filesystem->mkdir($targetPath);

        $fileHandle = fopen($targetPath . '/code-climate.json', 'wb');

        $iterator = DiagnosticIterator::filterByMinimumSeverity($report, $minimumSeverity)
            ->thenSortByFileAndRange()
            ->thenSortByTool();

        fwrite($fileHandle, "[\n");
        $addComma = false;
        $diagnostics = $iterator->getIterator();
        while ($diagnostics->valid()) {
            if ($addComma) {
                fwrite($fileHandle, ",\n");
            }

            fwrite($fileHandle, json_encode(self::convertDiagnostic($diagnostics)));
            $addComma = true;
        }
        if ($addComma) {
            fwrite($fileHandle, "\n");
        }

        fwrite($fileHandle, "]\n");

        fclose($fileHandle);
    }

    /**
     * @param Generator<int, DiagnosticIteratorEntry> $diagnostics
     * @return TCodeClimateIssue
     *
     * @see https://github.com/codeclimate/platform/blob/master/spec/analyzers/SPEC.md#data-types
     */
    private static function convertDiagnostic(Generator $diagnostics): array
    {
        $entry = $diagnostics->current();
        $error = $entry->getDiagnostic();
        $source = $error->getSource();
        $toolName = $entry->getTask()->getTaskName();
        $checkName = (null !== $source ? sprintf('%s: %s', $toolName, $source) : $toolName);
        $locations = self::convertRanges($diagnostics);
        $fingerprint = md5($checkName . json_encode($locations));

        $result = [
            // Required. Must always be "issue".
            'type' => 'issue',
            // Required. A unique name representing the static analysis check that emitted this issue.
            'check_name' => $checkName,
            // Required. A string explaining the issue that was detected.
            'description' => self::fixMessage(
                $error->getMessage() . (($url = $error->getExternalInfoUrl()) ? ' (See: ' . $url . ')' : '')
            ),
            // Optional. A markdown snippet describing the issue, including deeper explanations and links to other
            // resources.
            // 'content' => '',
            // Required. At least one category indicating the nature of the issue being reported.
            'categories' => self::mapCategories($error->getCategories()),
            // Required. A Location object representing the place in the source code where the issue was discovered.
            'location' => array_shift($locations),
            // Optional. An integer indicating a rough estimate of how long it would take to resolve the reported issue.
            // 'remediation_points' => 0,
            // Optional. A Severity string (info, minor, major, critical, or blocker) describing the potential impact
            // of the issue found.
            'severity' => self::mapSeverity($error->getSeverity()),
            // Optional. A unique, deterministic identifier for the specific issue being reported to allow a user to
            // exclude it from future analyses.
            'fingerprint' => $fingerprint,
        ];
        // Optional. A Trace object representing other interesting source code locations related to this issue.
        if (count($locations)) {
            $result['other_locations'] = $locations;
        }

        return $result;
    }

    /**
     * @param Generator<int, DiagnosticIteratorEntry> $diagnostics
     * @return TCodeClimateLocationList
     */
    private static function convertRanges(Generator $diagnostics): array
    {
        $entry = $diagnostics->current();
        if (!$entry->isFileRelated()) {
            $diagnostics->next();
            return [
                [
                    'path' => 'unknown',
                    'lines' => [
                        'begin' => 1,
                        'end' => 1,
                    ],
                ]
            ];
        }
        // Collect ranges for this diagnostic together.
        $diagnostic = $entry->getDiagnostic();
        $ranges = [];
        do {
            $range = $entry->getRange();
            if (null === $range) {
                break;
            }
            $ranges[] = self::mapLocation($range);
            $diagnostics->next();
            $entry = $diagnostics->current();
        } while ($diagnostics->valid() && $diagnostic === $entry->getDiagnostic());

        return $ranges;
    }

    /** @return TCodeClimateLocation */
    private static function mapLocation(FileRangeBuffer $range): array
    {
        // We override the null line numbers with the first line if none set as the line numbers are mandatory.
        $startLine = $range->getStartLine() ?? 1;
        $endLine = $range->getEndLine() ?? $startLine;
        $startCol = $range->getStartColumn();
        $endCol = $range->getEndColumn();

        if (null !== $startCol) {
            return [
                'path' => $range->getFile(),
                'positions' => [
                    'begin' => [
                        'line' => $startLine,
                        'column' => $startCol,
                    ],
                    'end' => [
                        'line' => $endLine,
                        'column' => $endCol ?? $startCol,
                    ],
                ],
            ];
        }
        return [
            'path' => $range->getFile(),
            'lines' => [
                'begin' => $startLine,
                'end' => $endLine,
            ],
        ];
    }

    private static function fixMessage(string $message): string
    {
        return str_replace("\n", ' ', $message);
    }

    /**
     * @param Generator<int, string> $categories
     *
     * @return list<TCodeClimateIssueCategory>
     */
    private static function mapCategories(Generator $categories): array
    {
        $mapped = [];
        foreach ($categories as $category) {
            $mapped[] = self::mapCategory($category);
        }

        // If no category given, default to 'Bug Risk'
        if ([] === $mapped) {
            $mapped[] = 'Bug Risk';
        }

        return $mapped;
    }

    /** @return TCodeClimateIssueCategory */
    private static function mapCategory(string $category): string
    {
        switch ($category) {
            case DiagnosticBuilderInterface::CATEGORY_CLARITY:
                return 'Clarity';
            case DiagnosticBuilderInterface::CATEGORY_COMPATIBILITY:
                return 'Compatibility';
            case DiagnosticBuilderInterface::CATEGORY_COMPLEXITY:
                return 'Complexity';
            case DiagnosticBuilderInterface::CATEGORY_DUPLICATION:
                return 'Duplication';
            case DiagnosticBuilderInterface::CATEGORY_PERFORMANCE:
                return 'Performance';
            case DiagnosticBuilderInterface::CATEGORY_SECURITY:
                return 'Security';
            case DiagnosticBuilderInterface::CATEGORY_STYLE:
                return 'Style';
            case DiagnosticBuilderInterface::CATEGORY_BUG_RISK:
            default:
        }
        return 'Bug Risk';
    }

    private static function mapSeverity(string $severity): string
    {
        switch ($severity) {
            case TaskReportInterface::SEVERITY_NONE:
            case TaskReportInterface::SEVERITY_INFO:
                return 'info';
            case TaskReportInterface::SEVERITY_MARGINAL:
            case TaskReportInterface::SEVERITY_MINOR:
                return 'minor';
            case TaskReportInterface::SEVERITY_MAJOR:
                return 'major';
            case TaskReportInterface::SEVERITY_FATAL:
            default:
                return 'blocker';
        }
    }
}
