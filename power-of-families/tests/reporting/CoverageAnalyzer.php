<?php

/**
 * Coverage Analyzer
 * 
 * Analyzes code coverage data and generates insights
 * 
 * @package Power_Of_Families
 */

class CoverageAnalyzer
{

    /**
     * Analyze Clover XML coverage data
     *
     * @param string $clover_file Path to Clover XML file
     * @return array Coverage analysis
     */
    public static function analyze_clover($clover_file)
    {
        if (!file_exists($clover_file)) {
            throw new InvalidArgumentException("Clover XML file not found: $clover_file");
        }

        $xml = simplexml_load_file($clover_file);
        if ($xml === false) {
            throw new RuntimeException("Failed to parse Clover XML file: $clover_file");
        }

        $analysis = [
            'project' => (string) $xml['name'] ?? 'Unknown Project',
            'timestamp' => (string) $xml['generated'] ?? date('c'),
            'overall' => [
                'percentage' => 0,
                'files' => 0,
                'lines' => 0,
                'functions' => 0,
                'classes' => 0,
            ],
            'files' => [],
            'trends' => [],
            'insights' => [],
        ];

        // Analyze project metrics
        $project_metrics = $xml->project->metrics[0];
        if ($project_metrics) {
            $analysis['overall'] = [
                'percentage' => self::calculate_percentage(
                    (int) $project_metrics['coveredstatements'],
                    (int) $project_metrics['statements']
                ),
                'files' => (int) $project_metrics['files'],
                'lines' => (int) $project_metrics['statements'],
                'functions' => (int) $project_metrics['coveredmethods'],
                'classes' => (int) $project_metrics['classes'],
            ];
        }

        // Analyze file-level coverage
        foreach ($xml->project->file as $file) {
            $file_metrics = $file->metrics[0];
            $file_data = [
                'name' => (string) $file['name'],
                'path' => (string) $file['path'],
                'coverage' => self::calculate_percentage(
                    (int) $file_metrics['coveredstatements'],
                    (int) $file_metrics['statements']
                ),
                'lines' => (int) $file_metrics['statements'],
                'covered_lines' => (int) $file_metrics['coveredstatements'],
                'functions' => (int) $file_metrics['methods'],
                'covered_functions' => (int) $file_metrics['coveredmethods'],
                'classes' => (int) $file_metrics['classes'],
                'complexity' => (int) $file_metrics['complexity'],
                'lines_data' => [],
            ];

            // Analyze line-level coverage
            foreach ($file->line as $line) {
                $file_data['lines_data'][] = [
                    'number' => (int) $line['num'],
                    'type' => (string) $line['type'],
                    'count' => (int) $line['count'],
                    'covered' => (int) $line['count'] > 0,
                ];
            }

            $analysis['files'][] = $file_data;
        }

        // Generate insights
        $analysis['insights'] = self::generate_insights($analysis);

        return $analysis;
    }

    /**
     * Calculate coverage percentage
     *
     * @param int $covered Covered items
     * @param int $total Total items
     * @return float Coverage percentage
     */
    private static function calculate_percentage($covered, $total)
    {
        if ($total === 0) {
            return 0.0;
        }
        return round(($covered / $total) * 100, 2);
    }

    /**
     * Generate coverage insights
     *
     * @param array $analysis Coverage analysis data
     * @return array Insights
     */
    private static function generate_insights($analysis)
    {
        $insights = [];

        $overall_coverage = $analysis['overall']['percentage'];

        // Overall coverage insights
        if ($overall_coverage >= 90) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Coverage',
                'message' => "Overall coverage is {$overall_coverage}%, which is excellent!",
                'recommendation' => 'Maintain this high level of coverage.',
            ];
        } elseif ($overall_coverage >= 80) {
            $insights[] = [
                'type' => 'good',
                'title' => 'Good Coverage',
                'message' => "Overall coverage is {$overall_coverage}%, which is good.",
                'recommendation' => 'Consider improving coverage for uncovered areas.',
            ];
        } elseif ($overall_coverage >= 50) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Moderate Coverage',
                'message' => "Overall coverage is {$overall_coverage}%, which is moderate.",
                'recommendation' => 'Focus on increasing test coverage for critical areas.',
            ];
        } else {
            $insights[] = [
                'type' => 'error',
                'title' => 'Low Coverage',
                'message' => "Overall coverage is {$overall_coverage}%, which is low.",
                'recommendation' => 'Significantly increase test coverage to improve code quality.',
            ];
        }

        // File-level insights
        $low_coverage_files = array_filter($analysis['files'], function ($file) {
            return $file['coverage'] < 50;
        });

        if (!empty($low_coverage_files)) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Low Coverage Files',
                'message' => count($low_coverage_files) . ' files have coverage below 50%.',
                'recommendation' => 'Focus on testing these files: ' . implode(', ', array_slice(array_column($low_coverage_files, 'name'), 0, 3)),
            ];
        }

        // Complexity insights
        $high_complexity_files = array_filter($analysis['files'], function ($file) {
            return $file['complexity'] > 10;
        });

        if (!empty($high_complexity_files)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'High Complexity Files',
                'message' => count($high_complexity_files) . ' files have high complexity.',
                'recommendation' => 'Consider refactoring complex files to improve maintainability.',
            ];
        }

        // Uncovered lines insights
        $uncovered_lines = 0;
        foreach ($analysis['files'] as $file) {
            $uncovered_lines += ($file['lines'] - $file['covered_lines']);
        }

        if ($uncovered_lines > 0) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Uncovered Lines',
                'message' => "{$uncovered_lines} lines are not covered by tests.",
                'recommendation' => 'Review uncovered lines to determine if they need tests.',
            ];
        }

        return $insights;
    }

    /**
     * Generate coverage trends
     *
     * @param array $historical_data Historical coverage data
     * @return array Trend analysis
     */
    public static function analyze_trends($historical_data)
    {
        if (empty($historical_data) || count($historical_data) < 2) {
            return [
                'trend' => 'stable',
                'change' => 0,
                'message' => 'Insufficient data for trend analysis',
            ];
        }

        $latest = end($historical_data);
        $previous = prev($historical_data);
        $change = $latest['coverage'] - $previous['coverage'];

        $trend = 'stable';
        if ($change > 1) {
            $trend = 'improving';
        } elseif ($change < -1) {
            $trend = 'declining';
        }

        return [
            'trend' => $trend,
            'change' => round($change, 2),
            'message' => self::get_trend_message($trend, $change),
            'recommendation' => self::get_trend_recommendation($trend, $change),
        ];
    }

    /**
     * Get trend message
     *
     * @param string $trend Trend direction
     * @param float $change Change amount
     * @return string Trend message
     */
    private static function get_trend_message($trend, $change)
    {
        switch ($trend) {
            case 'improving':
                return "Coverage is improving by {$change}%";
            case 'declining':
                return "Coverage is declining by " . abs($change) . "%";
            default:
                return "Coverage is stable";
        }
    }

    /**
     * Get trend recommendation
     *
     * @param string $trend Trend direction
     * @param float $change Change amount
     * @return string Recommendation
     */
    private static function get_trend_recommendation($trend, $change)
    {
        switch ($trend) {
            case 'improving':
                return 'Continue the good work! Keep adding tests to maintain this positive trend.';
            case 'declining':
                return 'Investigate why coverage is declining and add tests for new or modified code.';
            default:
                return 'Consider adding more tests to improve overall coverage.';
        }
    }

    /**
     * Generate coverage report in different formats
     *
     * @param array $analysis Coverage analysis
     * @param string $format Output format (html, json, csv, markdown)
     * @param string $output_file Output file path
     * @return bool Success status
     */
    public static function generate_report($analysis, $format, $output_file)
    {
        switch ($format) {
            case 'html':
                return self::generate_html_report($analysis, $output_file);
            case 'json':
                return file_put_contents($output_file, json_encode($analysis, JSON_PRETTY_PRINT)) !== false;
            case 'csv':
                return self::generate_csv_report($analysis, $output_file);
            case 'markdown':
                return self::generate_markdown_report($analysis, $output_file);
            default:
                throw new InvalidArgumentException("Unsupported report format: $format");
        }
    }

    /**
     * Generate HTML coverage report
     *
     * @param array $analysis Coverage analysis
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private static function generate_html_report($analysis, $output_file)
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coverage Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .insight { margin: 10px 0; padding: 15px; border-radius: 5px; }
        .insight.success { background: #d4edda; border-left: 4px solid #28a745; }
        .insight.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .insight.error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .insight.info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Coverage Analysis Report</h1>
        <p><strong>Project:</strong> ' . htmlspecialchars($analysis['project']) . '</p>
        <p><strong>Generated:</strong> ' . htmlspecialchars($analysis['timestamp']) . '</p>
        <p><strong>Overall Coverage:</strong> ' . $analysis['overall']['percentage'] . '%</p>
    </div>
    
    <h2>Insights</h2>';

        foreach ($analysis['insights'] as $insight) {
            $html .= '<div class="insight ' . $insight['type'] . '">
                <h3>' . htmlspecialchars($insight['title']) . '</h3>
                <p>' . htmlspecialchars($insight['message']) . '</p>
                <p><strong>Recommendation:</strong> ' . htmlspecialchars($insight['recommendation']) . '</p>
            </div>';
        }

        $html .= '<h2>File Coverage Details</h2>
    <table>
        <tr><th>File</th><th>Coverage</th><th>Lines</th><th>Functions</th><th>Complexity</th></tr>';

        foreach ($analysis['files'] as $file) {
            $html .= '<tr>
                <td>' . htmlspecialchars($file['name']) . '</td>
                <td>' . $file['coverage'] . '%</td>
                <td>' . $file['covered_lines'] . '/' . $file['lines'] . '</td>
                <td>' . $file['covered_functions'] . '/' . $file['functions'] . '</td>
                <td>' . $file['complexity'] . '</td>
            </tr>';
        }

        $html .= '</table></body></html>';

        return file_put_contents($output_file, $html) !== false;
    }

    /**
     * Generate CSV coverage report
     *
     * @param array $analysis Coverage analysis
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private static function generate_csv_report($analysis, $output_file)
    {
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            return false;
        }

        // Write header
        fputcsv($handle, ['File', 'Coverage %', 'Lines', 'Covered Lines', 'Functions', 'Covered Functions', 'Complexity']);

        // Write data
        foreach ($analysis['files'] as $file) {
            fputcsv($handle, [
                $file['name'],
                $file['coverage'],
                $file['lines'],
                $file['covered_lines'],
                $file['functions'],
                $file['covered_functions'],
                $file['complexity'],
            ]);
        }

        fclose($handle);
        return true;
    }

    /**
     * Generate Markdown coverage report
     *
     * @param array $analysis Coverage analysis
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private static function generate_markdown_report($analysis, $output_file)
    {
        $markdown = "# Coverage Analysis Report\n\n";
        $markdown .= "**Project:** " . $analysis['project'] . "\n";
        $markdown .= "**Generated:** " . $analysis['timestamp'] . "\n";
        $markdown .= "**Overall Coverage:** " . $analysis['overall']['percentage'] . "%\n\n";

        $markdown .= "## Insights\n\n";
        foreach ($analysis['insights'] as $insight) {
            $markdown .= "### " . $insight['title'] . "\n";
            $markdown .= $insight['message'] . "\n\n";
            $markdown .= "**Recommendation:** " . $insight['recommendation'] . "\n\n";
        }

        $markdown .= "## File Coverage Details\n\n";
        $markdown .= "| File | Coverage | Lines | Functions | Complexity |\n";
        $markdown .= "|------|----------|-------|-----------|------------|\n";

        foreach ($analysis['files'] as $file) {
            $markdown .= "| " . $file['name'] . " | " . $file['coverage'] . "% | " .
                $file['covered_lines'] . "/" . $file['lines'] . " | " .
                $file['covered_functions'] . "/" . $file['functions'] . " | " .
                $file['complexity'] . " |\n";
        }

        return file_put_contents($output_file, $markdown) !== false;
    }
}
