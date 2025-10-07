#!/bin/bash

# Test Performance Monitoring Script
# Monitors and analyzes test performance metrics

set -e

# Default values
VERBOSE="${VERBOSE:-false}"
OUTPUT_FORMAT="${OUTPUT_FORMAT:-text}"
OUTPUT_FILE="${OUTPUT_FILE:-}"
MONITOR_INTERVAL="${MONITOR_INTERVAL:-1}"
MONITOR_DURATION="${MONITOR_DURATION:-60}"
MEMORY_LIMIT="${MEMORY_LIMIT:-2G}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
NC='\033[0m' # No Color

# Display usage information
show_usage() {
    echo -e "${BLUE}Test Performance Monitoring Script${NC}"
    echo ""
    echo -e "${YELLOW}Usage:${NC}"
    echo "  $0 [COMMAND] [OPTIONS]"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    echo "  monitor                  Monitor test performance in real-time"
    echo "  analyze                  Analyze test performance from logs"
    echo "  benchmark                Run performance benchmarks"
    echo "  memory-test              Test memory usage patterns"
    echo "  cpu-test                 Test CPU usage patterns"
    echo "  slow-tests               Identify slow-running tests"
    echo "  memory-leaks             Check for memory leaks"
    echo "  performance-report       Generate performance report"
    echo "  compare-runs             Compare performance between runs"
    echo "  optimize-suggestions    Suggest performance optimizations"
    echo "  help                     Show this help message"
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --output-format FORMAT   Output format (text, json, csv, html)"
    echo "  --output-file FILE       Output file path"
    echo "  --monitor-interval SEC   Monitor interval in seconds (default: 1)"
    echo "  --monitor-duration SEC   Monitor duration in seconds (default: 60)"
    echo "  --memory-limit LIMIT     PHP memory limit (default: 2G)"
    echo "  --verbose                Enable verbose output"
    echo "  --help                   Show this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 monitor --monitor-duration 120        # Monitor for 2 minutes"
    echo "  $0 analyze --output-format json          # Analyze in JSON format"
    echo "  $0 benchmark --memory-limit 4G          # Benchmark with 4GB memory"
    echo "  $0 slow-tests --output-file slow.txt    # Save slow tests to file"
}

# Parse command line arguments
COMMAND="${1:-help}"
shift

while [[ $# -gt 0 ]]; do
    case $1 in
        --output-format)
            OUTPUT_FORMAT="$2"
            shift 2
            ;;
        --output-file)
            OUTPUT_FILE="$2"
            shift 2
            ;;
        --monitor-interval)
            MONITOR_INTERVAL="$2"
            shift 2
            ;;
        --monitor-duration)
            MONITOR_DURATION="$2"
            shift 2
            ;;
        --memory-limit)
            MEMORY_LIMIT="$2"
            shift 2
            ;;
        --verbose)
            VERBOSE="true"
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

log_performance() {
    echo -e "${CYAN}📊 $1${NC}"
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${PURPLE}🔍 $1${NC}"
    fi
}

# Monitor test performance in real-time
monitor_performance() {
    log_performance "Starting performance monitoring..."
    log_info "Duration: ${MONITOR_DURATION}s, Interval: ${MONITOR_INTERVAL}s"
    echo ""
    
    local start_time=$(date +%s)
    local end_time=$((start_time + MONITOR_DURATION))
    local metrics_file="/tmp/test-performance-$(date +%s).log"
    
    # Start test execution in background
    log_info "Starting test execution..."
    docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml --verbose > "$metrics_file" 2>&1 &
    local test_pid=$!
    
    # Monitor performance
    while [ $(date +%s) -lt $end_time ] && kill -0 $test_pid 2>/dev/null; do
        local current_time=$(date +%s)
        local elapsed=$((current_time - start_time))
        
        # Get system metrics
        local cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
        local memory_usage=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
        local disk_usage=$(df -h . | tail -1 | awk '{print $5}')
        
        # Get Docker container metrics
        local container_cpu=$(docker stats --no-stream --format "table {{.CPUPerc}}" test 2>/dev/null | tail -1 || echo "N/A")
        local container_memory=$(docker stats --no-stream --format "table {{.MemUsage}}" test 2>/dev/null | tail -1 || echo "N/A")
        
        # Display metrics
        printf "\r${CYAN}⏱️  %02d:%02d | CPU: %s%% | Memory: %s%% | Disk: %s | Container CPU: %s | Container Memory: %s${NC}" \
            $((elapsed / 60)) $((elapsed % 60)) \
            "$cpu_usage" "$memory_usage" "$disk_usage" \
            "$container_cpu" "$container_memory"
        
        sleep "$MONITOR_INTERVAL"
    done
    
    echo ""
    echo ""
    
    # Wait for test completion
    if kill -0 $test_pid 2>/dev/null; then
        log_info "Waiting for test completion..."
        wait $test_pid
    fi
    
    # Analyze results
    log_performance "Performance monitoring complete"
    analyze_performance_logs "$metrics_file"
    
    # Cleanup
    rm -f "$metrics_file"
}

# Analyze performance from logs
analyze_performance_logs() {
    local log_file="${1:-/tmp/test-performance.log}"
    
    if [ ! -f "$log_file" ]; then
        log_error "Log file not found: $log_file"
        return 1
    fi
    
    log_performance "Analyzing performance logs..."
    echo ""
    
    # Extract test execution metrics
    local total_tests=$(grep -o "Tests: [0-9]*" "$log_file" | tail -1 | grep -o "[0-9]*" || echo "0")
    local total_time=$(grep -o "Time: [0-9.]*" "$log_file" | tail -1 | grep -o "[0-9.]*" || echo "0")
    local memory_peak=$(grep -o "Peak memory: [0-9.]*" "$log_file" | tail -1 | grep -o "[0-9.]*" || echo "0")
    
    # Calculate performance metrics
    local avg_time_per_test=0
    if [ "$total_tests" -gt 0 ] && [ "$total_time" != "0" ]; then
        avg_time_per_test=$(echo "scale=4; $total_time / $total_tests" | bc)
    fi
    
    # Display analysis
    echo -e "${WHITE}📊 Performance Analysis${NC}"
    echo "====================="
    echo -e "Total Tests: ${GREEN}$total_tests${NC}"
    echo -e "Total Time: ${GREEN}${total_time}s${NC}"
    echo -e "Average Time per Test: ${GREEN}${avg_time_per_test}s${NC}"
    echo -e "Peak Memory: ${GREEN}${memory_peak}MB${NC}"
    echo ""
    
    # Performance recommendations
    echo -e "${YELLOW}💡 Performance Recommendations:${NC}"
    if (( $(echo "$avg_time_per_test > 1.0" | bc -l) )); then
        echo "- Consider optimizing slow tests"
    fi
    
    if (( $(echo "$memory_peak > 512" | bc -l) )); then
        echo "- Consider reducing memory usage"
    fi
    
    if [ "$total_tests" -gt 100 ]; then
        echo "- Consider running tests in parallel"
    fi
    
    echo ""
}

# Run performance benchmarks
run_benchmarks() {
    log_performance "Running performance benchmarks..."
    echo ""
    
    local benchmark_results="/tmp/benchmark-results-$(date +%s).json"
    
    # Run multiple test iterations
    local iterations=3
    local total_time=0
    local total_memory=0
    
    for i in $(seq 1 $iterations); do
        log_info "Benchmark iteration $i/$iterations..."
        
        local start_time=$(date +%s.%N)
        local start_memory=$(free -m | grep Mem | awk '{print $3}')
        
        # Run tests
        if docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml >/dev/null 2>&1; then
            local end_time=$(date +%s.%N)
            local end_memory=$(free -m | grep Mem | awk '{print $3}')
            
            local iteration_time=$(echo "$end_time - $start_time" | bc)
            local iteration_memory=$((end_memory - start_memory))
            
            total_time=$(echo "$total_time + $iteration_time" | bc)
            total_memory=$((total_memory + iteration_memory))
            
            log_verbose "Iteration $i: ${iteration_time}s, ${iteration_memory}MB"
        else
            log_error "Benchmark iteration $i failed"
            return 1
        fi
    done
    
    # Calculate averages
    local avg_time=$(echo "scale=4; $total_time / $iterations" | bc)
    local avg_memory=$((total_memory / iterations))
    
    # Display results
    echo ""
    echo -e "${WHITE}📊 Benchmark Results${NC}"
    echo "==================="
    echo -e "Iterations: ${GREEN}$iterations${NC}"
    echo -e "Average Time: ${GREEN}${avg_time}s${NC}"
    echo -e "Average Memory: ${GREEN}${avg_memory}MB${NC}"
    echo ""
    
    # Save results
    cat > "$benchmark_results" << EOF
{
    "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
    "iterations": $iterations,
    "average_time": $avg_time,
    "average_memory": $avg_memory,
    "memory_limit": "$MEMORY_LIMIT"
}
EOF
    
    log_success "Benchmark results saved to: $benchmark_results"
}

# Test memory usage patterns
test_memory_usage() {
    log_performance "Testing memory usage patterns..."
    echo ""
    
    # Run tests with memory monitoring
    local memory_log="/tmp/memory-usage-$(date +%s).log"
    
    log_info "Running tests with memory monitoring..."
    
    if docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml --verbose 2>&1 | tee "$memory_log"; then
        log_success "Memory usage test completed"
        
        # Analyze memory usage
        local peak_memory=$(grep -o "Peak memory: [0-9.]*" "$memory_log" | tail -1 | grep -o "[0-9.]*" || echo "0")
        local memory_limit_mb=$(echo "$MEMORY_LIMIT" | sed 's/G/000/' | sed 's/M//')
        
        echo ""
        echo -e "${WHITE}📊 Memory Usage Analysis${NC}"
        echo "======================="
        echo -e "Peak Memory: ${GREEN}${peak_memory}MB${NC}"
        echo -e "Memory Limit: ${GREEN}${memory_limit_mb}MB${NC}"
        
        local memory_percentage=$(echo "scale=2; $peak_memory * 100 / $memory_limit_mb" | bc)
        echo -e "Memory Usage: ${GREEN}${memory_percentage}%${NC}"
        
        if (( $(echo "$memory_percentage > 80" | bc -l) )); then
            log_warning "High memory usage detected"
        fi
        
        # Cleanup
        rm -f "$memory_log"
    else
        log_error "Memory usage test failed"
        return 1
    fi
}

# Test CPU usage patterns
test_cpu_usage() {
    log_performance "Testing CPU usage patterns..."
    echo ""
    
    # Monitor CPU usage during test execution
    local cpu_log="/tmp/cpu-usage-$(date +%s).log"
    
    log_info "Running tests with CPU monitoring..."
    
    # Start CPU monitoring
    (while true; do
        echo "$(date +%s.%N) $(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)"
        sleep 1
    done) > "$cpu_log" &
    local monitor_pid=$!
    
    # Run tests
    if docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml >/dev/null 2>&1; then
        # Stop monitoring
        kill $monitor_pid 2>/dev/null || true
        wait $monitor_pid 2>/dev/null || true
        
        log_success "CPU usage test completed"
        
        # Analyze CPU usage
        local avg_cpu=$(awk '{sum+=$2; count++} END {if(count>0) print sum/count; else print 0}' "$cpu_log")
        local max_cpu=$(awk '{if($2>max) max=$2} END {print max+0}' "$cpu_log")
        
        echo ""
        echo -e "${WHITE}📊 CPU Usage Analysis${NC}"
        echo "===================="
        echo -e "Average CPU: ${GREEN}${avg_cpu}%${NC}"
        echo -e "Peak CPU: ${GREEN}${max_cpu}%${NC}"
        
        if (( $(echo "$avg_cpu > 80" | bc -l) )); then
            log_warning "High CPU usage detected"
        fi
        
        # Cleanup
        rm -f "$cpu_log"
    else
        # Stop monitoring
        kill $monitor_pid 2>/dev/null || true
        wait $monitor_pid 2>/dev/null || true
        
        log_error "CPU usage test failed"
        rm -f "$cpu_log"
        return 1
    fi
}

# Identify slow-running tests
identify_slow_tests() {
    log_performance "Identifying slow-running tests..."
    echo ""
    
    # Run tests with timing information
    local timing_log="/tmp/test-timing-$(date +%s).log"
    
    log_info "Running tests with timing analysis..."
    
    if docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml --verbose 2>&1 | tee "$timing_log"; then
        log_success "Slow test analysis completed"
        
        # Extract slow tests
        local slow_tests=$(grep -E "\.\.\. [0-9]+\.[0-9]+s" "$timing_log" | sort -k2 -nr | head -10)
        
        if [ -n "$slow_tests" ]; then
            echo ""
            echo -e "${WHITE}🐌 Slowest Tests${NC}"
            echo "==============="
            echo "$slow_tests" | while read -r line; do
                local test_name=$(echo "$line" | awk '{print $1}')
                local test_time=$(echo "$line" | awk '{print $2}')
                echo -e "  ${GREEN}$test_name${NC} - ${YELLOW}$test_time${NC}"
            done
        else
            log_info "No slow tests identified"
        fi
        
        # Save results
        if [ -n "$OUTPUT_FILE" ]; then
            echo "$slow_tests" > "$OUTPUT_FILE"
            log_success "Slow tests saved to: $OUTPUT_FILE"
        fi
        
        # Cleanup
        rm -f "$timing_log"
    else
        log_error "Slow test analysis failed"
        rm -f "$timing_log"
        return 1
    fi
}

# Check for memory leaks
check_memory_leaks() {
    log_performance "Checking for memory leaks..."
    echo ""
    
    # Run tests multiple times to detect memory leaks
    local leak_log="/tmp/memory-leak-$(date +%s).log"
    local iterations=5
    
    log_info "Running $iterations iterations to detect memory leaks..."
    
    for i in $(seq 1 $iterations); do
        log_info "Iteration $i/$iterations..."
        
        local start_memory=$(free -m | grep Mem | awk '{print $3}')
        
        # Run tests
        if docker-compose run --rm test php -d memory_limit="$MEMORY_LIMIT" phpunit --configuration phpunit.xml >/dev/null 2>&1; then
            local end_memory=$(free -m | grep Mem | awk '{print $3}')
            local memory_diff=$((end_memory - start_memory))
            
            echo "$i $memory_diff" >> "$leak_log"
            log_verbose "Iteration $i: ${memory_diff}MB memory difference"
        else
            log_error "Iteration $i failed"
            rm -f "$leak_log"
            return 1
        fi
    done
    
    # Analyze memory leak patterns
    local memory_trend=$(awk '{sum+=$2; count++} END {if(count>0) print sum/count; else print 0}' "$leak_log")
    local memory_variance=$(awk -v mean="$memory_trend" '{sum+=($2-mean)^2; count++} END {if(count>0) print sum/count; else print 0}' "$leak_log")
    
    echo ""
    echo -e "${WHITE}📊 Memory Leak Analysis${NC}"
    echo "======================="
    echo -e "Average Memory Difference: ${GREEN}${memory_trend}MB${NC}"
    echo -e "Memory Variance: ${GREEN}${memory_variance}MB${NC}"
    
    if (( $(echo "$memory_trend > 50" | bc -l) )); then
        log_warning "Potential memory leak detected"
    else
        log_success "No significant memory leaks detected"
    fi
    
    # Cleanup
    rm -f "$leak_log"
}

# Generate performance report
generate_performance_report() {
    log_performance "Generating performance report..."
    echo ""
    
    local report_file="${OUTPUT_FILE:-test-performance-report-$(date +%Y%m%d-%H%M%S).html}"
    
    # Collect performance data
    local total_tests=$(docker-compose run --rm test phpunit --configuration phpunit.xml 2>&1 | grep -o "Tests: [0-9]*" | tail -1 | grep -o "[0-9]*" || echo "0")
    local total_time=$(docker-compose run --rm test phpunit --configuration phpunit.xml 2>&1 | grep -o "Time: [0-9.]*" | tail -1 | grep -o "[0-9.]*" || echo "0")
    local peak_memory=$(docker-compose run --rm test phpunit --configuration phpunit.xml 2>&1 | grep -o "Peak memory: [0-9.]*" | tail -1 | grep -o "[0-9.]*" || echo "0")
    
    # Generate HTML report
    cat > "$report_file" << EOF
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Performance Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f5f5f5; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .metric { background: white; padding: 15px; border-radius: 5px; margin-bottom: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .metric h3 { margin: 0 0 10px 0; color: #333; }
        .metric .value { font-size: 2em; font-weight: bold; color: #28a745; }
        .recommendations { background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Test Performance Report</h1>
        <p>Generated on: $(date)</p>
    </div>
    
    <div class="metric">
        <h3>Total Tests</h3>
        <div class="value">$total_tests</div>
    </div>
    
    <div class="metric">
        <h3>Total Execution Time</h3>
        <div class="value">${total_time}s</div>
    </div>
    
    <div class="metric">
        <h3>Peak Memory Usage</h3>
        <div class="value">${peak_memory}MB</div>
    </div>
    
    <div class="recommendations">
        <h3>Performance Recommendations</h3>
        <ul>
            <li>Consider running tests in parallel for faster execution</li>
            <li>Optimize slow tests to improve overall performance</li>
            <li>Monitor memory usage to prevent memory leaks</li>
            <li>Use appropriate memory limits for test execution</li>
        </ul>
    </div>
</body>
</html>
EOF
    
    log_success "Performance report generated: $report_file"
}

# Compare performance between runs
compare_performance_runs() {
    log_performance "Comparing performance between runs..."
    echo ""
    
    log_info "This feature requires performance data from previous runs"
    log_info "Run 'generate-performance-report' to create baseline data"
    
    # This would typically compare against stored performance data
    log_warning "Performance comparison not implemented yet"
}

# Suggest performance optimizations
suggest_optimizations() {
    log_performance "Analyzing performance for optimization suggestions..."
    echo ""
    
    # Run basic performance analysis
    local total_tests=$(docker-compose run --rm test phpunit --configuration phpunit.xml 2>&1 | grep -o "Tests: [0-9]*" | tail -1 | grep -o "[0-9]*" || echo "0")
    local total_time=$(docker-compose run --rm test phpunit --configuration phpunit.xml 2>&1 | grep -o "Time: [0-9.]*" | tail -1 | grep -o "[0-9.]*" || echo "0")
    
    echo -e "${WHITE}💡 Performance Optimization Suggestions${NC}"
    echo "====================================="
    echo ""
    
    # Calculate average time per test
    local avg_time_per_test=0
    if [ "$total_tests" -gt 0 ] && [ "$total_time" != "0" ]; then
        avg_time_per_test=$(echo "scale=4; $total_time / $total_tests" | bc)
    fi
    
    echo -e "${YELLOW}Based on current performance:${NC}"
    echo "- Total Tests: $total_tests"
    echo "- Total Time: ${total_time}s"
    echo "- Average Time per Test: ${avg_time_per_test}s"
    echo ""
    
    echo -e "${YELLOW}Optimization Suggestions:${NC}"
    
    if (( $(echo "$avg_time_per_test > 1.0" | bc -l) )); then
        echo "• Consider optimizing slow tests (average > 1s per test)"
    fi
    
    if [ "$total_tests" -gt 50 ]; then
        echo "• Consider running tests in parallel (many tests detected)"
    fi
    
    if (( $(echo "$total_time > 60" | bc -l) )); then
        echo "• Consider test suite optimization (total time > 60s)"
    fi
    
    echo "• Use appropriate memory limits for test execution"
    echo "• Monitor for memory leaks in long-running tests"
    echo "• Consider using test data factories for faster setup"
    echo "• Optimize database operations in tests"
    echo ""
}

# Main execution
main() {
    case "$COMMAND" in
        monitor)
            monitor_performance
            ;;
        analyze)
            analyze_performance_logs "$1"
            ;;
        benchmark)
            run_benchmarks
            ;;
        memory-test)
            test_memory_usage
            ;;
        cpu-test)
            test_cpu_usage
            ;;
        slow-tests)
            identify_slow_tests
            ;;
        memory-leaks)
            check_memory_leaks
            ;;
        performance-report)
            generate_performance_report
            ;;
        compare-runs)
            compare_performance_runs
            ;;
        optimize-suggestions)
            suggest_optimizations
            ;;
        help)
            show_usage
            ;;
        *)
            log_error "Unknown command: $COMMAND"
            show_usage
            exit 1
            ;;
    esac
}

# Run main function
main
