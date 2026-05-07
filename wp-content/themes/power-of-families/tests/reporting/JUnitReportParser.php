<?php

/**
 * JUnit Report Parser and Analyzer
 * 
 * Parses JUnit XML reports and provides analysis functionality
 * 
 * @package Power_Of_Families
 */

class JUnitReportParser
{

    /**
     * Parse JUnit XML report
     *
     * @param string $xml_file Path to JUnit XML file
     * @return array Parsed report data
     */
    public static function parse($xml_file)
    {
        if (!file_exists($xml_file)) {
            throw new InvalidArgumentException("JUnit XML file not found: $xml_file");
        }

        $xml = simplexml_load_file($xml_file);
        if ($xml === false) {
            throw new RuntimeException("Failed to parse JUnit XML file: $xml_file");
        }

        $report = [
            'timestamp' => (string) ($xml['timestamp'] ?? date('c')),
            'testsuites' => [],
            'summary' => [
                'total_tests' => 0,
                'total_failures' => 0,
                'total_errors' => 0,
                'total_skipped' => 0,
                'total_time' => 0,
            ],
        ];

        foreach ($xml->testsuite as $testsuite) {
            $suite_data = [
                'name' => (string) $testsuite['name'],
                'tests' => (int) $testsuite['tests'],
                'failures' => (int) $testsuite['failures'],
                'errors' => (int) $testsuite['errors'],
                'skipped' => (int) $testsuite['skipped'],
                'time' => (float) $testsuite['time'],
                'timestamp' => (string) ($testsuite['timestamp'] ?? ''),
                'testcases' => [],
                'properties' => [],
            ];

            // Parse test cases
            foreach ($testsuite->testcase as $testcase) {
                $case_data = [
                    'name' => (string) $testcase['name'],
                    'class' => (string) $testcase['classname'],
                    'time' => (float) $testcase['time'],
                    'status' => 'passed',
                    'message' => '',
                    'type' => '',
                ];

                // Check for failures
                if (isset($testcase->failure)) {
                    $case_data['status'] = 'failed';
                    $case_data['message'] = (string) $testcase->failure;
                    $case_data['type'] = (string) $testcase->failure['type'];
                }

                // Check for errors
                if (isset($testcase->error)) {
                    $case_data['status'] = 'error';
                    $case_data['message'] = (string) $testcase->error;
                    $case_data['type'] = (string) $testcase->error['type'];
                }

                // Check for skipped
                if (isset($testcase->skipped)) {
                    $case_data['status'] = 'skipped';
                    $case_data['message'] = (string) $testcase->skipped;
                }

                $suite_data['testcases'][] = $case_data;
            }

            // Parse properties
            if (isset($testsuite->properties)) {
                foreach ($testsuite->properties->property as $property) {
                    $suite_data['properties'][(string) $property['name']] = (string) $property['value'];
                }
            }

            $report['testsuites'][] = $suite_data;

            // Update summary
            $report['summary']['total_tests'] += $suite_data['tests'];
            $report['summary']['total_failures'] += $suite_data['failures'];
            $report['summary']['total_errors'] += $suite_data['errors'];
            $report['summary']['total_skipped'] += $suite_data['skipped'];
            $report['summary']['total_time'] += $suite_data['time'];
        }

        return $report;
    }

    /**
     * Generate test summary statistics
     *
     * @param array $report Parsed report data
     * @return array Summary statistics
     */
    public static function generate_summary($report)
    {
        $summary = $report['summary'];

        $passed = $summary['total_tests'] - $summary['total_failures'] - $summary['total_errors'] - $summary['total_skipped'];

        return [
            'total_tests' => $summary['total_tests'],
            'passed' => $passed,
            'failed' => $summary['total_failures'],
            'errors' => $summary['total_errors'],
            'skipped' => $summary['total_skipped'],
            'success_rate' => $summary['total_tests'] > 0 ? round(($passed / $summary['total_tests']) * 100, 2) : 0,
            'execution_time' => $summary['total_time'],
            'average_time_per_test' => $summary['total_tests'] > 0 ? round($summary['total_time'] / $summary['total_tests'], 4) : 0,
        ];
    }

    /**
     * Analyze test performance
     *
     * @param array $report Parsed report data
     * @return array Performance analysis
     */
    public static function analyze_performance($report)
    {
        $all_times = [];
        $slow_tests = [];
        $fast_tests = [];

        foreach ($report['testsuites'] as $testsuite) {
            foreach ($testsuite['testcases'] as $testcase) {
                $all_times[] = $testcase['time'];

                if ($testcase['time'] > 1.0) {
                    $slow_tests[] = [
                        'name' => $testcase['name'],
                        'class' => $testcase['class'],
                        'time' => $testcase['time'],
                    ];
                } elseif ($testcase['time'] < 0.1) {
                    $fast_tests[] = [
                        'name' => $testcase['name'],
                        'class' => $testcase['class'],
                        'time' => $testcase['time'],
                    ];
                }
            }
        }

        sort($all_times);
        $count = count($all_times);

        return [
            'total_execution_time' => array_sum($all_times),
            'average_time' => $count > 0 ? round(array_sum($all_times) / $count, 4) : 0,
            'median_time' => $count > 0 ? round($all_times[intval($count / 2)], 4) : 0,
            'min_time' => $count > 0 ? round(min($all_times), 4) : 0,
            'max_time' => $count > 0 ? round(max($all_times), 4) : 0,
            'slow_tests' => $slow_tests,
            'fast_tests' => $fast_tests,
            'slow_test_count' => count($slow_tests),
            'fast_test_count' => count($fast_tests),
        ];
    }

    /**
     * Generate coverage analysis
     *
     * @param array $report Parsed report data
     * @return array Coverage analysis
     */
    public static function analyze_coverage($report)
    {
        $coverage_data = [
            'enabled' => false,
            'percentage' => 0,
            'files_covered' => 0,
            'files_total' => 0,
            'lines_covered' => 0,
            'lines_total' => 0,
        ];

        foreach ($report['testsuites'] as $testsuite) {
            if (
                isset($testsuite['properties']['coverage.enabled']) &&
                $testsuite['properties']['coverage.enabled'] === 'true'
            ) {
                $coverage_data['enabled'] = true;

                if (isset($testsuite['properties']['coverage.percentage'])) {
                    $coverage_data['percentage'] = (float) $testsuite['properties']['coverage.percentage'];
                }

                if (isset($testsuite['properties']['coverage.files_covered'])) {
                    $coverage_data['files_covered'] = (int) $testsuite['properties']['coverage.files_covered'];
                }

                if (isset($testsuite['properties']['coverage.files_total'])) {
                    $coverage_data['files_total'] = (int) $testsuite['properties']['coverage.files_total'];
                }

                if (isset($testsuite['properties']['coverage.lines_covered'])) {
                    $coverage_data['lines_covered'] = (int) $testsuite['properties']['coverage.lines_covered'];
                }

                if (isset($testsuite['properties']['coverage.lines_total'])) {
                    $coverage_data['lines_total'] = (int) $testsuite['properties']['coverage.lines_total'];
                }

                break;
            }
        }

        return $coverage_data;
    }

    /**
     * Generate failure analysis
     *
     * @param array $report Parsed report data
     * @return array Failure analysis
     */
    public static function analyze_failures($report)
    {
        $failures = [];
        $errors = [];
        $failure_types = [];
        $error_types = [];

        foreach ($report['testsuites'] as $testsuite) {
            foreach ($testsuite['testcases'] as $testcase) {
                if ($testcase['status'] === 'failed') {
                    $failures[] = [
                        'name' => $testcase['name'],
                        'class' => $testcase['class'],
                        'message' => $testcase['message'],
                        'type' => $testcase['type'],
                        'time' => $testcase['time'],
                    ];

                    if (!isset($failure_types[$testcase['type']])) {
                        $failure_types[$testcase['type']] = 0;
                    }
                    $failure_types[$testcase['type']]++;
                } elseif ($testcase['status'] === 'error') {
                    $errors[] = [
                        'name' => $testcase['name'],
                        'class' => $testcase['class'],
                        'message' => $testcase['message'],
                        'type' => $testcase['type'],
                        'time' => $testcase['time'],
                    ];

                    if (!isset($error_types[$testcase['type']])) {
                        $error_types[$testcase['type']] = 0;
                    }
                    $error_types[$testcase['type']]++;
                }
            }
        }

        return [
            'failures' => $failures,
            'errors' => $errors,
            'failure_types' => $failure_types,
            'error_types' => $error_types,
            'failure_count' => count($failures),
            'error_count' => count($errors),
            'total_issues' => count($failures) + count($errors),
        ];
    }

    /**
     * Export report to different formats
     *
     * @param array $report Parsed report data
     * @param string $format Output format (json, csv, html)
     * @param string $output_file Output file path
     * @return bool Success status
     */
    public static function export($report, $format, $output_file)
    {
        switch ($format) {
            case 'json':
                return file_put_contents($output_file, json_encode($report, JSON_PRETTY_PRINT)) !== false;

            case 'csv':
                return self::export_csv($report, $output_file);

            case 'html':
                return self::export_html($report, $output_file);

            default:
                throw new InvalidArgumentException("Unsupported export format: $format");
        }
    }

    /**
     * Export report to CSV format
     *
     * @param array $report Parsed report data
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private static function export_csv($report, $output_file)
    {
        $handle = fopen($output_file, 'w');
        if (!$handle) {
            return false;
        }

        // Write header
        fputcsv($handle, ['Test Suite', 'Test Case', 'Class', 'Status', 'Time', 'Message']);

        // Write data
        foreach ($report['testsuites'] as $testsuite) {
            foreach ($testsuite['testcases'] as $testcase) {
                fputcsv($handle, [
                    $testsuite['name'],
                    $testcase['name'],
                    $testcase['class'],
                    $testcase['status'],
                    $testcase['time'],
                    $testcase['message'],
                ]);
            }
        }

        fclose($handle);
        return true;
    }

    /**
     * Export report to HTML format
     *
     * @param array $report Parsed report data
     * @param string $output_file Output file path
     * @return bool Success status
     */
    private static function export_html($report, $output_file)
    {
        $summary = self::generate_summary($report);
        $performance = self::analyze_performance($report);
        $coverage = self::analyze_coverage($report);
        $failures = self::analyze_failures($report);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JUnit Test Report Analysis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .summary { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .passed { color: green; }
        .failed { color: red; }
        .error { color: red; }
        .skipped { color: orange; }
    </style>
</head>
<body>
    <h1>JUnit Test Report Analysis</h1>
    
    <div class="summary">
        <h2>Summary</h2>
        <p><strong>Total Tests:</strong> ' . $summary['total_tests'] . '</p>
        <p><strong>Passed:</strong> <span class="passed">' . $summary['passed'] . '</span></p>
        <p><strong>Failed:</strong> <span class="failed">' . $summary['failed'] . '</span></p>
        <p><strong>Errors:</strong> <span class="error">' . $summary['errors'] . '</span></p>
        <p><strong>Skipped:</strong> <span class="skipped">' . $summary['skipped'] . '</span></p>
        <p><strong>Success Rate:</strong> ' . $summary['success_rate'] . '%</p>
        <p><strong>Execution Time:</strong> ' . $summary['execution_time'] . 's</p>
    </div>';

        if ($coverage['enabled']) {
            $html .= '
    <div class="section">
        <h2>Code Coverage</h2>
        <p><strong>Coverage:</strong> ' . $coverage['percentage'] . '%</p>
        <p><strong>Files Covered:</strong> ' . $coverage['files_covered'] . '/' . $coverage['files_total'] . '</p>
    </div>';
        }

        if ($failures['total_issues'] > 0) {
            $html .= '
    <div class="section">
        <h2>Issues</h2>
        <table>
            <tr><th>Test</th><th>Class</th><th>Status</th><th>Message</th></tr>';

            foreach ($failures['failures'] as $failure) {
                $html .= '<tr><td>' . htmlspecialchars($failure['name']) . '</td><td>' .
                    htmlspecialchars($failure['class']) . '</td><td class="failed">Failed</td><td>' .
                    htmlspecialchars(substr($failure['message'], 0, 100)) . '...</td></tr>';
            }

            foreach ($failures['errors'] as $error) {
                $html .= '<tr><td>' . htmlspecialchars($error['name']) . '</td><td>' .
                    htmlspecialchars($error['class']) . '</td><td class="error">Error</td><td>' .
                    htmlspecialchars(substr($error['message'], 0, 100)) . '...</td></tr>';
            }

            $html .= '</table></div>';
        }

        $html .= '</body></html>';

        return file_put_contents($output_file, $html) !== false;
    }
}
