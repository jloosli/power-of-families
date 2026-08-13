#!/bin/bash

# JUnit Test Report Generator
# Generates comprehensive JUnit XML reports for CI/CD integration

set -e

# Default values
OUTPUT_DIR="${OUTPUT_DIR:-test-reports}"
JUNIT_FILE="${JUNIT_FILE:-junit.xml}"
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
VERBOSE="${VERBOSE:-false}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Compute overall line-coverage percentage from a Clover XML file.
# PHPUnit's Clover format stores totals on //project/metrics and has NO
# @percentage attribute on the root <coverage> element, so we derive it.
# Querying //coverage/@percentage here wrote an empty coverage.percentage
# property into every junit.xml.
#
# One of five copies (see bin/ci-coverage-integration.sh, bin/run-tests-ci.sh,
# bin/run-tests-with-reporting.sh and bin/manage-coverage-thresholds.sh).
# Candidate 05 of the deepening review proposes a shared bin/lib/common.sh;
# this set is exactly what it would collapse.
clover_percentage() {
    local file="$1"
    local statements covered
    statements=$(xmlstarlet sel -t -v "//project/metrics/@statements" "$file" 2>/dev/null || echo "0")
    covered=$(xmlstarlet sel -t -v "//project/metrics/@coveredstatements" "$file" 2>/dev/null || echo "0")
    statements=${statements:-0}
    covered=${covered:-0}
    if [ "$statements" -gt 0 ] 2>/dev/null; then
        printf '%.2f' "$(echo "scale=4; $covered * 100 / $statements" | bc -l)"
    else
        echo "0"
    fi
}

# Display usage information
show_usage() {
    echo -e "${BLUE}JUnit Test Report Generator${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --output-dir DIR    Output directory for reports (default: test-reports)"
    echo "  --junit-file FILE   JUnit XML filename (default: junit.xml)"
    echo "  --coverage-dir DIR  Coverage directory (default: coverage)"
    echo "  --verbose           Enable verbose output"
    echo "  --help              Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  OUTPUT_DIR          Output directory for reports"
    echo "  JUNIT_FILE          JUnit XML filename"
    echo "  COVERAGE_DIR        Coverage directory"
    echo "  VERBOSE             Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Generate basic JUnit report"
    echo "  $0 --output-dir reports --verbose    # Generate with custom output and verbose"
    echo "  OUTPUT_DIR=reports $0                # Use environment variable"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --output-dir)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        --junit-file)
            JUNIT_FILE="$2"
            shift 2
            ;;
        --coverage-dir)
            COVERAGE_DIR="$2"
            shift 2
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            show_usage
            exit 1
            ;;
    esac
done

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}" >&2
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${YELLOW}🔍 $1${NC}"
    fi
}

# Create output directory
create_output_directory() {
    log_info "Creating output directory: $OUTPUT_DIR"
    mkdir -p "$OUTPUT_DIR"
    log_success "Output directory created"
}

# Generate JUnit XML report
generate_junit_report() {
    log_info "Generating JUnit XML report..."
    
    local junit_file="$OUTPUT_DIR/$JUNIT_FILE"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Start JUnit XML
    cat > "$junit_file" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
    <testsuite name="Power of Families Theme Tests" 
               tests="0" 
               failures="0" 
               errors="0" 
               skipped="0" 
               time="0" 
               timestamp="$timestamp">
EOF

    # Add test cases from PHPUnit output if available
    if [ -f "$OUTPUT_DIR/phpunit-junit.xml" ]; then
        log_verbose "Merging PHPUnit JUnit output"
        # Extract test cases from PHPUnit JUnit output
        if command -v xmlstarlet >/dev/null 2>&1; then
            xmlstarlet sel -t -c "//testcase" "$OUTPUT_DIR/phpunit-junit.xml" >> "$junit_file"
        else
            # Fallback: simple text processing
            grep -A 10 "<testcase" "$OUTPUT_DIR/phpunit-junit.xml" >> "$junit_file" || true
        fi
    fi

    # Add coverage information if available
    if [ -f "$COVERAGE_DIR/clover.xml" ]; then
        log_verbose "Adding coverage information"
        echo "        <properties>" >> "$junit_file"
        echo "            <property name=\"coverage.enabled\" value=\"true\"/>" >> "$junit_file"
        
        # Extract coverage metrics from Clover XML
        if command -v xmlstarlet >/dev/null 2>&1; then
            local coverage_percent=$(clover_percentage "$COVERAGE_DIR/clover.xml")
            echo "            <property name=\"coverage.percentage\" value=\"$coverage_percent\"/>" >> "$junit_file"
        fi
        
        echo "        </properties>" >> "$junit_file"
    fi

    # End JUnit XML
    cat >> "$junit_file" << EOF
    </testsuite>
</testsuites>
EOF

    log_success "JUnit XML report generated: $junit_file"
}

# Generate test summary report
generate_test_summary() {
    log_info "Generating test summary report..."
    
    local summary_file="$OUTPUT_DIR/test-summary.json"
    
    cat > "$summary_file" << EOF
{
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "test_suite": "Power of Families Theme Tests",
    "environment": {
        "php_version": "$(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'unknown')",
        "phpunit_version": "$(phpunit --version 2>/dev/null | head -n1 || echo 'unknown')",
        "wordpress_version": "6.8.3",
        "theme_version": "3.0.0"
    },
    "test_results": {
        "total_tests": 0,
        "passed": 0,
        "failed": 0,
        "skipped": 0,
        "errors": 0
    },
    "coverage": {
        "enabled": false,
        "percentage": 0,
        "files_covered": 0,
        "files_total": 0
    },
    "performance": {
        "execution_time": 0,
        "memory_usage": 0
    }
}
EOF

    log_success "Test summary report generated: $summary_file"
}

# Generate HTML test report
generate_html_report() {
    log_info "Generating HTML test report..."
    
    local html_file="$OUTPUT_DIR/test-report.html"
    
    cat > "$html_file" << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power of Families Theme - Test Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 2em;
        }
        .stat-card p {
            margin: 0;
            color: #666;
            font-size: 0.9em;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .test-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .test-item:last-child {
            border-bottom: none;
        }
        .test-name {
            font-weight: 500;
            color: #333;
        }
        .test-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .status-passed {
            background: #d4edda;
            color: #155724;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .status-skipped {
            background: #fff3cd;
            color: #856404;
        }
        .coverage-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        .coverage-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Power of Families Theme</h1>
            <p>Test Report - Generated on <span id="timestamp"></span></p>
        </div>
        
        <div class="content">
            <div class="stats">
                <div class="stat-card">
                    <h3 id="total-tests">0</h3>
                    <p>Total Tests</p>
                </div>
                <div class="stat-card">
                    <h3 id="passed-tests">0</h3>
                    <p>Passed</p>
                </div>
                <div class="stat-card">
                    <h3 id="failed-tests">0</h3>
                    <p>Failed</p>
                </div>
                <div class="stat-card">
                    <h3 id="coverage-percent">0%</h3>
                    <p>Code Coverage</p>
                </div>
            </div>
            
            <div class="section">
                <h2>Test Results</h2>
                <div class="test-list" id="test-results">
                    <p>No test results available.</p>
                </div>
            </div>
            
            <div class="section">
                <h2>Code Coverage</h2>
                <div class="coverage-bar">
                    <div class="coverage-fill" id="coverage-bar" style="width: 0%"></div>
                </div>
                <p id="coverage-text">Coverage data not available.</p>
            </div>
        </div>
        
        <div class="footer">
            <p>Generated by Power of Families Test Suite</p>
        </div>
    </div>
    
    <script>
        // Update timestamp
        document.getElementById('timestamp').textContent = new Date().toLocaleString();
        
        // Load test data from JSON if available
        fetch('test-summary.json')
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-tests').textContent = data.test_results.total_tests;
                document.getElementById('passed-tests').textContent = data.test_results.passed;
                document.getElementById('failed-tests').textContent = data.test_results.failed;
                
                if (data.coverage.enabled) {
                    const coveragePercent = Math.round(data.coverage.percentage);
                    document.getElementById('coverage-percent').textContent = coveragePercent + '%';
                    document.getElementById('coverage-bar').style.width = coveragePercent + '%';
                    document.getElementById('coverage-text').textContent = 
                        `Coverage: ${coveragePercent}% (${data.coverage.files_covered}/${data.coverage.files_total} files)`;
                }
            })
            .catch(error => {
                console.log('Could not load test summary data:', error);
            });
    </script>
</body>
</html>
EOF

    log_success "HTML test report generated: $html_file"
}

# Validate JUnit XML
validate_junit_xml() {
    log_info "Validating JUnit XML report..."
    
    local junit_file="$OUTPUT_DIR/$JUNIT_FILE"
    
    if [ ! -f "$junit_file" ]; then
        log_error "JUnit XML file not found: $junit_file"
        return 1
    fi
    
    # Basic XML validation
    if command -v xmllint >/dev/null 2>&1; then
        if xmllint --noout "$junit_file" 2>/dev/null; then
            log_success "JUnit XML is valid"
        else
            log_error "JUnit XML validation failed"
            return 1
        fi
    else
        log_warning "xmllint not available, skipping XML validation"
    fi
    
    # Check for required elements
    if grep -q "<testsuites>" "$junit_file" && grep -q "<testsuite" "$junit_file"; then
        log_success "JUnit XML contains required elements"
    else
        log_error "JUnit XML missing required elements"
        return 1
    fi
}

# Main execution
main() {
    echo -e "${BLUE}📊 JUnit Test Report Generator${NC}"
    echo ""
    
    # Create output directory
    create_output_directory
    
    # Generate reports
    generate_junit_report
    generate_test_summary
    generate_html_report
    
    # Validate output
    validate_junit_xml
    
    log_success "JUnit reporting setup completed!"
    echo ""
    echo -e "${BLUE}📁 Generated Files:${NC}"
    echo -e "  JUnit XML: ${GREEN}$OUTPUT_DIR/$JUNIT_FILE${NC}"
    echo -e "  Test Summary: ${GREEN}$OUTPUT_DIR/test-summary.json${NC}"
    echo -e "  HTML Report: ${GREEN}$OUTPUT_DIR/test-report.html${NC}"
    echo ""
    echo -e "${BLUE}💡 Next Steps:${NC}"
    echo -e "  View HTML report: ${GREEN}open $OUTPUT_DIR/test-report.html${NC}"
    echo -e "  Use in CI/CD: ${GREEN}$OUTPUT_DIR/$JUNIT_FILE${NC}"
}

# Run main function
main
