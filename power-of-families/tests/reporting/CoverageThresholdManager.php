<?php

/**
 * Coverage Threshold Manager
 * 
 * Manages coverage thresholds and quality gates for CI/CD integration
 * 
 * @package Power_Of_Families
 */

class CoverageThresholdManager
{

    /**
     * Default threshold values
     */
    const DEFAULT_THRESHOLDS = [
        'overall_coverage' => [
            'minimum' => 50,
            'target' => 80,
            'high' => 90,
            'critical' => 30,
        ],
        'file_coverage' => [
            'minimum' => 40,
            'target' => 70,
            'high' => 85,
            'critical' => 20,
        ],
        'function_coverage' => [
            'minimum' => 60,
            'target' => 80,
            'high' => 95,
            'critical' => 40,
        ],
        'class_coverage' => [
            'minimum' => 70,
            'target' => 85,
            'high' => 95,
            'critical' => 50,
        ],
        'line_coverage' => [
            'minimum' => 50,
            'target' => 80,
            'high' => 90,
            'critical' => 30,
        ],
    ];

    /**
     * Default quality gate values
     */
    const DEFAULT_QUALITY_GATES = [
        'max_uncovered_lines' => 100,
        'max_low_coverage_files' => 5,
        'max_complexity' => 10,
        'max_cyclomatic_complexity' => 15,
        'min_test_count' => 10,
        'max_failure_rate' => 5,
    ];

    /**
     * Thresholds configuration
     */
    private $thresholds;

    /**
     * Quality gates configuration
     */
    private $quality_gates;

    /**
     * Coverage data
     */
    private $coverage_data;

    /**
     * Constructor
     *
     * @param array $thresholds Custom thresholds configuration
     * @param array $quality_gates Custom quality gates configuration
     */
    public function __construct($thresholds = null, $quality_gates = null)
    {
        $this->thresholds = $thresholds ?? self::DEFAULT_THRESHOLDS;
        $this->quality_gates = $quality_gates ?? self::DEFAULT_QUALITY_GATES;
    }

    /**
     * Load thresholds from file
     *
     * @param string $file_path Path to thresholds file
     * @return bool Success status
     */
    public function load_thresholds($file_path)
    {
        if (!file_exists($file_path)) {
            return false;
        }

        $data = json_decode(file_get_contents($file_path), true);
        if ($data === null) {
            return false;
        }

        $this->thresholds = $data['thresholds'] ?? self::DEFAULT_THRESHOLDS;
        $this->quality_gates = $data['quality_gates'] ?? self::DEFAULT_QUALITY_GATES;

        return true;
    }

    /**
     * Save thresholds to file
     *
     * @param string $file_path Path to thresholds file
     * @param string $project_name Project name
     * @return bool Success status
     */
    public function save_thresholds($file_path, $project_name = 'Power of Families Theme')
    {
        $data = [
            'project' => $project_name,
            'version' => '1.0.0',
            'last_updated' => date('c'),
            'thresholds' => $this->thresholds,
            'quality_gates' => $this->quality_gates,
            'exclusions' => [
                'files' => [
                    'vendor/**',
                    'node_modules/**',
                    'tests/**',
                    '**/*.min.js',
                    '**/*.min.css'
                ],
                'directories' => [
                    'vendor',
                    'node_modules',
                    'tests',
                    'coverage',
                    'dist'
                ],
                'patterns' => [
                    '**/test_*.php',
                    '**/*Test.php',
                    '**/*_test.php'
                ]
            ],
            'enforcement' => [
                'fail_on_threshold_breach' => true,
                'fail_on_quality_gate_failure' => true,
                'warn_on_threshold_breach' => false,
                'warn_on_quality_gate_failure' => true
            ]
        ];

        return file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Set coverage data
     *
     * @param array $coverage_data Coverage data
     */
    public function set_coverage_data($coverage_data)
    {
        $this->coverage_data = $coverage_data;
    }

    /**
     * Check coverage against thresholds
     *
     * @return array Check results
     */
    public function check_thresholds()
    {
        if (empty($this->coverage_data)) {
            throw new RuntimeException('Coverage data not set');
        }

        $results = [
            'status' => 'PASS',
            'thresholds_met' => true,
            'issues' => [],
            'warnings' => [],
            'recommendations' => [],
        ];

        $overall_coverage = $this->coverage_data['overall_coverage'] ?? 0;
        $minimum_threshold = $this->thresholds['overall_coverage']['minimum'];
        $target_threshold = $this->thresholds['overall_coverage']['target'];
        $high_threshold = $this->thresholds['overall_coverage']['high'];

        // Check overall coverage
        if ($overall_coverage < $minimum_threshold) {
            $results['status'] = 'FAIL';
            $results['thresholds_met'] = false;
            $results['issues'][] = "Overall coverage ({$overall_coverage}%) is below minimum threshold ({$minimum_threshold}%)";
        } elseif ($overall_coverage < $target_threshold) {
            if ($results['status'] === 'PASS') {
                $results['status'] = 'WARN';
            }
            $results['warnings'][] = "Overall coverage ({$overall_coverage}%) is below target threshold ({$target_threshold}%)";
        }

        // Check uncovered lines
        $uncovered_lines = $this->coverage_data['uncovered_lines'] ?? 0;
        $max_uncovered_lines = $this->quality_gates['max_uncovered_lines'];

        if ($uncovered_lines > $max_uncovered_lines) {
            if ($results['status'] === 'PASS') {
                $results['status'] = 'WARN';
            }
            $results['warnings'][] = "Uncovered lines ({$uncovered_lines}) exceed maximum allowed ({$max_uncovered_lines})";
        }

        // Check low coverage files
        $low_coverage_files = $this->coverage_data['low_coverage_files'] ?? 0;
        $max_low_coverage_files = $this->quality_gates['max_low_coverage_files'];

        if ($low_coverage_files > $max_low_coverage_files) {
            if ($results['status'] === 'PASS') {
                $results['status'] = 'WARN';
            }
            $results['warnings'][] = "Low coverage files ({$low_coverage_files}) exceed maximum allowed ({$max_low_coverage_files})";
        }

        // Generate recommendations
        $results['recommendations'] = $this->generate_recommendations($results);

        return $results;
    }

    /**
     * Validate quality gates
     *
     * @return array Validation results
     */
    public function validate_quality_gates()
    {
        if (empty($this->coverage_data)) {
            throw new RuntimeException('Coverage data not set');
        }

        $results = [
            'status' => 'PASS',
            'gates_passed' => 0,
            'gates_failed' => 0,
            'gates_warned' => 0,
            'gate_results' => [],
        ];

        $overall_coverage = $this->coverage_data['overall_coverage'] ?? 0;
        $uncovered_lines = $this->coverage_data['uncovered_lines'] ?? 0;
        $low_coverage_files = $this->coverage_data['low_coverage_files'] ?? 0;

        // Overall Coverage Gate
        $min_threshold = $this->thresholds['overall_coverage']['minimum'];
        if ($overall_coverage >= $min_threshold) {
            $results['gate_results'][] = [
                'name' => 'Overall Coverage Gate',
                'status' => 'PASS',
                'message' => "Overall coverage ({$overall_coverage}%) meets minimum threshold ({$min_threshold}%)",
            ];
            $results['gates_passed']++;
        } else {
            $results['gate_results'][] = [
                'name' => 'Overall Coverage Gate',
                'status' => 'FAIL',
                'message' => "Overall coverage ({$overall_coverage}%) below minimum threshold ({$min_threshold}%)",
            ];
            $results['gates_failed']++;
            $results['status'] = 'FAIL';
        }

        // Uncovered Lines Gate
        $max_uncovered = $this->quality_gates['max_uncovered_lines'];
        if ($uncovered_lines <= $max_uncovered) {
            $results['gate_results'][] = [
                'name' => 'Uncovered Lines Gate',
                'status' => 'PASS',
                'message' => "Uncovered lines ({$uncovered_lines}) within acceptable limit ({$max_uncovered})",
            ];
            $results['gates_passed']++;
        } else {
            $results['gate_results'][] = [
                'name' => 'Uncovered Lines Gate',
                'status' => 'WARN',
                'message' => "Uncovered lines ({$uncovered_lines}) exceed limit ({$max_uncovered})",
            ];
            $results['gates_warned']++;
            if ($results['status'] === 'PASS') {
                $results['status'] = 'WARN';
            }
        }

        // Low Coverage Files Gate
        $max_low_coverage = $this->quality_gates['max_low_coverage_files'];
        if ($low_coverage_files <= $max_low_coverage) {
            $results['gate_results'][] = [
                'name' => 'Low Coverage Files Gate',
                'status' => 'PASS',
                'message' => "Low coverage files ({$low_coverage_files}) within acceptable limit ({$max_low_coverage})",
            ];
            $results['gates_passed']++;
        } else {
            $results['gate_results'][] = [
                'name' => 'Low Coverage Files Gate',
                'status' => 'WARN',
                'message' => "Low coverage files ({$low_coverage_files}) exceed limit ({$max_low_coverage})",
            ];
            $results['gates_warned']++;
            if ($results['status'] === 'PASS') {
                $results['status'] = 'WARN';
            }
        }

        return $results;
    }

    /**
     * Generate recommendations based on check results
     *
     * @param array $check_results Check results
     * @return array Recommendations
     */
    private function generate_recommendations($check_results)
    {
        $recommendations = [];

        if ($check_results['status'] === 'FAIL') {
            $recommendations[] = 'Focus on increasing test coverage to meet minimum thresholds';
            $recommendations[] = 'Review uncovered code areas and add appropriate tests';
            $recommendations[] = 'Consider refactoring complex code to improve testability';
        } elseif ($check_results['status'] === 'WARN') {
            $recommendations[] = 'Consider improving test coverage to reach target thresholds';
            $recommendations[] = 'Review warning areas and add tests where appropriate';
        } else {
            $recommendations[] = 'Maintain current coverage levels';
            $recommendations[] = 'Consider aiming for high coverage threshold';
        }

        return $recommendations;
    }

    /**
     * Update thresholds based on current coverage
     *
     * @return bool Success status
     */
    public function update_thresholds_from_coverage()
    {
        if (empty($this->coverage_data)) {
            throw new RuntimeException('Coverage data not set');
        }

        $current_coverage = $this->coverage_data['overall_coverage'] ?? 0;

        // Update thresholds based on current coverage
        $this->thresholds['overall_coverage']['minimum'] = $current_coverage;
        $this->thresholds['overall_coverage']['target'] = $current_coverage + 10;
        $this->thresholds['overall_coverage']['high'] = $current_coverage + 20;

        return true;
    }

    /**
     * Get threshold status for a specific metric
     *
     * @param string $metric Metric name
     * @param float $value Current value
     * @return array Status information
     */
    public function get_threshold_status($metric, $value)
    {
        if (!isset($this->thresholds[$metric])) {
            throw new InvalidArgumentException("Unknown metric: $metric");
        }

        $thresholds = $this->thresholds[$metric];
        $status = 'PASS';
        $level = 'high';

        if ($value < $thresholds['critical']) {
            $status = 'CRITICAL';
            $level = 'critical';
        } elseif ($value < $thresholds['minimum']) {
            $status = 'FAIL';
            $level = 'minimum';
        } elseif ($value < $thresholds['target']) {
            $status = 'WARN';
            $level = 'target';
        } elseif ($value < $thresholds['high']) {
            $status = 'PASS';
            $level = 'high';
        } else {
            $status = 'EXCELLENT';
            $level = 'excellent';
        }

        return [
            'status' => $status,
            'level' => $level,
            'value' => $value,
            'thresholds' => $thresholds,
            'next_threshold' => $this->get_next_threshold($metric, $value),
        ];
    }

    /**
     * Get next threshold to achieve
     *
     * @param string $metric Metric name
     * @param float $value Current value
     * @return float Next threshold value
     */
    private function get_next_threshold($metric, $value)
    {
        $thresholds = $this->thresholds[$metric];

        if ($value < $thresholds['critical']) {
            return $thresholds['critical'];
        } elseif ($value < $thresholds['minimum']) {
            return $thresholds['minimum'];
        } elseif ($value < $thresholds['target']) {
            return $thresholds['target'];
        } elseif ($value < $thresholds['high']) {
            return $thresholds['high'];
        } else {
            return $thresholds['high']; // Already at highest level
        }
    }

    /**
     * Generate threshold compliance report
     *
     * @return array Compliance report
     */
    public function generate_compliance_report()
    {
        if (empty($this->coverage_data)) {
            throw new RuntimeException('Coverage data not set');
        }

        $threshold_check = $this->check_thresholds();
        $quality_gates_check = $this->validate_quality_gates();

        return [
            'project' => 'Power of Families Theme',
            'report_type' => 'threshold_compliance',
            'generated_at' => date('c'),
            'coverage_data' => $this->coverage_data,
            'thresholds' => $this->thresholds,
            'quality_gates' => $this->quality_gates,
            'compliance_status' => [
                'overall_status' => $threshold_check['status'],
                'thresholds_met' => $threshold_check['thresholds_met'],
                'quality_gates_passed' => $quality_gates_check['status'] !== 'FAIL',
                'issues' => $threshold_check['issues'],
                'warnings' => $threshold_check['warnings'],
                'recommendations' => $threshold_check['recommendations'],
            ],
            'threshold_check' => $threshold_check,
            'quality_gates_check' => $quality_gates_check,
        ];
    }

    /**
     * Export compliance report to different formats
     *
     * @param string $format Output format (json, html, csv, markdown)
     * @param string $output_file Output file path
     * @return bool Success status
     */
    public function export_compliance_report($format, $output_file)
    {
        $report = $this->generate_compliance_report();

        switch ($format) {
            case 'json':
                return file_put_contents($output_file, json_encode($report, JSON_PRETTY_PRINT)) !== false;

            case 'html':
                return $this->export_html_report($report, $output_file);

            case 'csv':
                return $this->export_csv_report($report, $output_file);

            case 'markdown':
                return $this->export_markdown_report($report, $output_file);

            default:
                throw new InvalidArgumentException("Unsupported export format: $format");
        }
    }

    /**
     * Export HTML compliance report
     *
     * @param array $report Compliance report
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private function export_html_report($report, $output_file)
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coverage Threshold Compliance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .status-pass { color: green; }
        .status-warn { color: orange; }
        .status-fail { color: red; }
        .status-critical { color: darkred; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Coverage Threshold Compliance Report</h1>
        <p><strong>Project:</strong> ' . htmlspecialchars($report['project']) . '</p>
        <p><strong>Generated:</strong> ' . htmlspecialchars($report['generated_at']) . '</p>
        <p><strong>Overall Status:</strong> <span class="status-' . strtolower($report['compliance_status']['overall_status']) . '">' . $report['compliance_status']['overall_status'] . '</span></p>
    </div>
    
    <h2>Coverage Data</h2>
    <table>
        <tr><th>Metric</th><th>Value</th><th>Status</th></tr>';

        foreach ($report['coverage_data'] as $metric => $value) {
            $html .= '<tr><td>' . htmlspecialchars($metric) . '</td><td>' . $value . '</td><td>-</td></tr>';
        }

        $html .= '</table>
    
    <h2>Issues</h2>';

        if (!empty($report['compliance_status']['issues'])) {
            $html .= '<ul>';
            foreach ($report['compliance_status']['issues'] as $issue) {
                $html .= '<li class="status-fail">' . htmlspecialchars($issue) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>No issues found.</p>';
        }

        $html .= '<h2>Warnings</h2>';

        if (!empty($report['compliance_status']['warnings'])) {
            $html .= '<ul>';
            foreach ($report['compliance_status']['warnings'] as $warning) {
                $html .= '<li class="status-warn">' . htmlspecialchars($warning) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>No warnings found.</p>';
        }

        $html .= '<h2>Recommendations</h2>
    <ul>';

        foreach ($report['compliance_status']['recommendations'] as $recommendation) {
            $html .= '<li>' . htmlspecialchars($recommendation) . '</li>';
        }

        $html .= '</ul></body></html>';

        return file_put_contents($output_file, $html) !== false;
    }

    /**
     * Export CSV compliance report
     *
     * @param array $report Compliance report
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private function export_csv_report($report, $output_file)
    {
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            return false;
        }

        // Write header
        fputcsv($handle, ['Metric', 'Value', 'Status', 'Threshold Met']);

        // Write coverage data
        foreach ($report['coverage_data'] as $metric => $value) {
            fputcsv($handle, [$metric, $value, '-', '-']);
        }

        fclose($handle);
        return true;
    }

    /**
     * Export Markdown compliance report
     *
     * @param array $report Compliance report
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private function export_markdown_report($report, $output_file)
    {
        $markdown = "# Coverage Threshold Compliance Report\n\n";
        $markdown .= "**Project:** " . $report['project'] . "\n";
        $markdown .= "**Generated:** " . $report['generated_at'] . "\n";
        $markdown .= "**Overall Status:** " . $report['compliance_status']['overall_status'] . "\n\n";

        $markdown .= "## Coverage Data\n\n";
        $markdown .= "| Metric | Value |\n";
        $markdown .= "|--------|-------|\n";

        foreach ($report['coverage_data'] as $metric => $value) {
            $markdown .= "| " . $metric . " | " . $value . " |\n";
        }

        $markdown .= "\n## Issues\n\n";
        if (!empty($report['compliance_status']['issues'])) {
            foreach ($report['compliance_status']['issues'] as $issue) {
                $markdown .= "- " . $issue . "\n";
            }
        } else {
            $markdown .= "No issues found.\n";
        }

        $markdown .= "\n## Warnings\n\n";
        if (!empty($report['compliance_status']['warnings'])) {
            foreach ($report['compliance_status']['warnings'] as $warning) {
                $markdown .= "- " . $warning . "\n";
            }
        } else {
            $markdown .= "No warnings found.\n";
        }

        $markdown .= "\n## Recommendations\n\n";
        foreach ($report['compliance_status']['recommendations'] as $recommendation) {
            $markdown .= "- " . $recommendation . "\n";
        }

        return file_put_contents($output_file, $markdown) !== false;
    }
}
