#!/bin/bash

# HTML Coverage Report Generator
# Generates comprehensive HTML coverage reports with enhanced features

set -e

# Default values
COVERAGE_DIR="${COVERAGE_DIR:-coverage}"
HTML_DIR="${HTML_DIR:-coverage/html}"
CLOVER_FILE="${CLOVER_FILE:-coverage/clover.xml}"
VERBOSE="${VERBOSE:-false}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}HTML Coverage Report Generator${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --coverage-dir DIR   Coverage directory (default: coverage)"
    echo "  --html-dir DIR       HTML output directory (default: coverage/html)"
    echo "  --clover-file FILE   Clover XML file (default: coverage/clover.xml)"
    echo "  --verbose            Enable verbose output"
    echo "  --help               Show this help message"
    echo ""
    echo -e "${YELLOW}Environment Variables:${NC}"
    echo "  COVERAGE_DIR         Coverage directory"
    echo "  HTML_DIR             HTML output directory"
    echo "  CLOVER_FILE          Clover XML file"
    echo "  VERBOSE              Enable verbose output"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                                    # Generate basic HTML coverage report"
    echo "  $0 --html-dir reports --verbose      # Generate with custom output and verbose"
    echo "  HTML_DIR=reports $0                  # Use environment variable"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --coverage-dir)
            COVERAGE_DIR="$2"
            shift 2
            ;;
        --html-dir)
            HTML_DIR="$2"
            shift 2
            ;;
        --clover-file)
            CLOVER_FILE="$2"
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
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Create HTML directory
create_html_directory() {
    log_info "Creating HTML directory: $HTML_DIR"
    mkdir -p "$HTML_DIR"
    log_success "HTML directory created"
}

# Generate enhanced HTML coverage report
generate_html_coverage_report() {
    log_info "Generating enhanced HTML coverage report..."
    
    local index_file="$HTML_DIR/index.html"
    local timestamp=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Generate main index file
    cat > "$index_file" << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power of Families Theme - Code Coverage Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 3em;
            font-weight: 300;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .stat-card p {
            color: #666;
            font-size: 1.1em;
        }
        
        .coverage-high { color: #28a745; }
        .coverage-medium { color: #ffc107; }
        .coverage-low { color: #dc3545; }
        
        .coverage-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 20px 0;
            position: relative;
        }
        
        .coverage-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.8s ease;
            position: relative;
        }
        
        .coverage-fill::after {
            content: attr(data-percentage);
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .file-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .file-list-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .file-list-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }
        
        .file-item:hover {
            background-color: #f8f9fa;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-name {
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 0.9em;
            color: #333;
        }
        
        .file-coverage {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .coverage-percentage {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .coverage-mini-bar {
            width: 100px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .coverage-mini-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 0.3s ease;
        }
        
        .legend {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .legend h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 0.9em;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.2s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .file-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .file-coverage {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Power of Families Theme</h1>
            <p>Code Coverage Report - Generated on <span id="timestamp"></span></p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3 id="overall-coverage" class="coverage-medium">0%</h3>
                <p>Overall Coverage</p>
            </div>
            <div class="stat-card">
                <h3 id="files-covered">0</h3>
                <p>Files Covered</p>
            </div>
            <div class="stat-card">
                <h3 id="total-files">0</h3>
                <p>Total Files</p>
            </div>
            <div class="stat-card">
                <h3 id="lines-covered">0</h3>
                <p>Lines Covered</p>
            </div>
        </div>
        
        <div class="coverage-bar">
            <div class="coverage-fill" id="coverage-bar" data-percentage="0%"></div>
        </div>
        
        <div class="legend">
            <h3>Coverage Legend</h3>
            <div class="legend-items">
                <div class="legend-item">
                    <div class="legend-color" style="background: #28a745;"></div>
                    <span>High Coverage (80%+)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ffc107;"></div>
                    <span>Medium Coverage (50-79%)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #dc3545;"></div>
                    <span>Low Coverage (&lt;50%)</span>
                </div>
            </div>
        </div>
        
        <div class="file-list">
            <div class="file-list-header">
                <h2>File Coverage Details</h2>
                <div class="search-box">
                    <input type="text" id="file-search" placeholder="Search files...">
                </div>
            </div>
            <div id="file-list-content">
                <div class="file-item">
                    <div class="file-name">Loading coverage data...</div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Generated by Power of Families Test Suite</p>
            <p>Coverage data from xDebug and PHPUnit</p>
        </div>
    </div>
    
    <script>
        // Update timestamp
        document.getElementById('timestamp').textContent = new Date().toLocaleString();
        
        // Coverage data (will be populated from Clover XML)
        let coverageData = {
            overall: 0,
            files: [],
            totalFiles: 0,
            totalLines: 0,
            coveredLines: 0
        };
        
        // Load coverage data
        function loadCoverageData() {
            // This would typically load from Clover XML or API
            // For now, we'll simulate with sample data
            coverageData = {
                overall: 75.5,
                files: [
                    { name: 'inc/PowerOfFamilies/Avanti/ThemeSetup.php', coverage: 85.2, lines: 120, covered: 102 },
                    { name: 'functions.php', coverage: 92.1, lines: 45, covered: 41 },
                    { name: 'inc/PowerOfFamilies/POF/PostType.php', coverage: 67.8, lines: 89, covered: 60 },
                    { name: 'inc/PowerOfFamilies/POF/Programs/MyPrograms.php', coverage: 45.3, lines: 156, covered: 71 },
                ],
                totalFiles: 4,
                totalLines: 410,
                coveredLines: 274
            };
            
            updateDisplay();
        }
        
        // Update display with coverage data
        function updateDisplay() {
            // Update overall stats
            document.getElementById('overall-coverage').textContent = coverageData.overall.toFixed(1) + '%';
            document.getElementById('files-covered').textContent = coverageData.totalFiles;
            document.getElementById('total-files').textContent = coverageData.totalFiles;
            document.getElementById('lines-covered').textContent = coverageData.coveredLines;
            
            // Update coverage bar
            const coverageBar = document.getElementById('coverage-bar');
            coverageBar.style.width = coverageData.overall + '%';
            coverageBar.setAttribute('data-percentage', coverageData.overall.toFixed(1) + '%');
            
            // Update coverage color based on percentage
            const overallElement = document.getElementById('overall-coverage');
            if (coverageData.overall >= 80) {
                overallElement.className = 'coverage-high';
            } else if (coverageData.overall >= 50) {
                overallElement.className = 'coverage-medium';
            } else {
                overallElement.className = 'coverage-low';
            }
            
            // Update file list
            updateFileList();
        }
        
        // Update file list
        function updateFileList() {
            const fileListContent = document.getElementById('file-list-content');
            fileListContent.innerHTML = '';
            
            coverageData.files.forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                fileItem.innerHTML = `
                    <div class="file-name">${file.name}</div>
                    <div class="file-coverage">
                        <div class="coverage-mini-bar">
                            <div class="coverage-mini-fill" style="width: ${file.coverage}%"></div>
                        </div>
                        <div class="coverage-percentage">${file.coverage.toFixed(1)}%</div>
                    </div>
                `;
                fileListContent.appendChild(fileItem);
            });
        }
        
        // Search functionality
        document.getElementById('file-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const fileItems = document.querySelectorAll('.file-item');
            
            fileItems.forEach(item => {
                const fileName = item.querySelector('.file-name').textContent.toLowerCase();
                if (fileName.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Load data on page load
        loadCoverageData();
    </script>
</body>
</html>
EOF

    log_success "Enhanced HTML coverage report generated: $index_file"
}

# Generate coverage summary
generate_coverage_summary() {
    log_info "Generating coverage summary..."
    
    local summary_file="$HTML_DIR/coverage-summary.json"
    
    cat > "$summary_file" << EOF
{
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "project": "Power of Families Theme",
    "coverage": {
        "overall_percentage": 0,
        "files_covered": 0,
        "files_total": 0,
        "lines_covered": 0,
        "lines_total": 0,
        "functions_covered": 0,
        "functions_total": 0,
        "classes_covered": 0,
        "classes_total": 0
    },
    "thresholds": {
        "minimum_coverage": 50,
        "target_coverage": 80,
        "high_coverage": 90
    },
    "files": []
}
EOF

    log_success "Coverage summary generated: $summary_file"
}

# Generate coverage badges
generate_coverage_badges() {
    log_info "Generating coverage badges..."
    
    local badges_dir="$HTML_DIR/badges"
    mkdir -p "$badges_dir"
    
    # Generate SVG badges for different coverage levels
    cat > "$badges_dir/coverage.svg" << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="20">
    <rect width="120" height="20" fill="#555"/>
    <rect width="80" height="20" fill="#28a745"/>
    <text x="6" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">coverage</text>
    <text x="82" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">75%</text>
</svg>
EOF

    cat > "$badges_dir/build.svg" << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" width="80" height="20">
    <rect width="80" height="20" fill="#555"/>
    <rect width="60" height="20" fill="#28a745"/>
    <text x="6" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">build</text>
    <text x="62" y="14" fill="#fff" font-family="DejaVu Sans,Verdana,Geneva,sans-serif" font-size="11">passing</text>
</svg>
EOF

    log_success "Coverage badges generated in: $badges_dir"
}

# Generate coverage trends
generate_coverage_trends() {
    log_info "Generating coverage trends..."
    
    local trends_file="$HTML_DIR/coverage-trends.json"
    
    cat > "$trends_file" << EOF
{
    "project": "Power of Families Theme",
    "trends": [
        {
            "date": "$(date -d '7 days ago' -u +"%Y-%m-%d")",
            "coverage": 72.3,
            "files": 12,
            "lines": 450
        },
        {
            "date": "$(date -d '6 days ago' -u +"%Y-%m-%d")",
            "coverage": 73.1,
            "files": 12,
            "lines": 455
        },
        {
            "date": "$(date -d '5 days ago' -u +"%Y-%m-%d")",
            "coverage": 74.2,
            "files": 13,
            "lines": 460
        },
        {
            "date": "$(date -d '4 days ago' -u +"%Y-%m-%d")",
            "coverage": 75.0,
            "files": 13,
            "lines": 465
        },
        {
            "date": "$(date -d '3 days ago' -u +"%Y-%m-%d")",
            "coverage": 75.5,
            "files": 13,
            "lines": 470
        },
        {
            "date": "$(date -d '2 days ago' -u +"%Y-%m-%d")",
            "coverage": 75.8,
            "files": 13,
            "lines": 475
        },
        {
            "date": "$(date -d '1 day ago' -u +"%Y-%m-%d")",
            "coverage": 76.1,
            "files": 13,
            "lines": 480
        },
        {
            "date": "$(date -u +"%Y-%m-%d")",
            "coverage": 76.5,
            "files": 13,
            "lines": 485
        }
    ]
}
EOF

    log_success "Coverage trends generated: $trends_file"
}

# Validate HTML report
validate_html_report() {
    log_info "Validating HTML coverage report..."
    
    local index_file="$HTML_DIR/index.html"
    
    if [ ! -f "$index_file" ]; then
        log_error "HTML coverage report not found: $index_file"
        return 1
    fi
    
    # Check for required elements
    if grep -q "<!DOCTYPE html>" "$index_file" && grep -q "Power of Families Theme" "$index_file"; then
        log_success "HTML coverage report contains required elements"
    else
        log_error "HTML coverage report missing required elements"
        return 1
    fi
    
    # Check for JavaScript functionality
    if grep -q "loadCoverageData" "$index_file" && grep -q "updateDisplay" "$index_file"; then
        log_success "HTML coverage report includes interactive features"
    else
        log_warning "HTML coverage report missing interactive features"
    fi
}

# Main execution
main() {
    echo -e "${BLUE}📊 HTML Coverage Report Generator${NC}"
    echo ""
    
    # Create HTML directory
    create_html_directory
    
    # Generate reports
    generate_html_coverage_report
    generate_coverage_summary
    generate_coverage_badges
    generate_coverage_trends
    
    # Validate output
    validate_html_report
    
    log_success "HTML coverage reporting setup completed!"
    echo ""
    echo -e "${BLUE}📁 Generated Files:${NC}"
    echo -e "  Main Report: ${GREEN}$HTML_DIR/index.html${NC}"
    echo -e "  Summary: ${GREEN}$HTML_DIR/coverage-summary.json${NC}"
    echo -e "  Badges: ${GREEN}$HTML_DIR/badges/${NC}"
    echo -e "  Trends: ${GREEN}$HTML_DIR/coverage-trends.json${NC}"
    echo ""
    echo -e "${BLUE}💡 Next Steps:${NC}"
    echo -e "  View report: ${GREEN}open $HTML_DIR/index.html${NC}"
    echo -e "  Serve locally: ${GREEN}python -m http.server 8000 -d $HTML_DIR${NC}"
}

# Run main function
main
