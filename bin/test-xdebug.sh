#!/bin/bash

# Test xDebug functionality in the WordPress container
# This script helps verify that xDebug is working correctly

set -e

echo "🔍 Testing xDebug configuration in WordPress container..."

# Check if xDebug is loaded
echo "📋 Checking if xDebug extension is loaded..."
docker-compose exec wordpress php -m | grep -i xdebug || {
    echo "❌ xDebug extension not found!"
    exit 1
}

echo "✅ xDebug extension is loaded"

# Check xDebug configuration
echo "📋 Checking xDebug configuration..."
docker-compose exec wordpress php -r "
\$config = ini_get_all('xdebug');
echo 'xDebug Configuration:' . PHP_EOL;
echo 'Mode: ' . (\$config['xdebug.mode']['global_value'] ?? 'not set') . PHP_EOL;
echo 'IDE Key: ' . (\$config['xdebug.idekey']['global_value'] ?? 'not set') . PHP_EOL;
echo 'Client Host: ' . (\$config['xdebug.client_host']['global_value'] ?? 'not set') . PHP_EOL;
echo 'Client Port: ' . (\$config['xdebug.client_port']['global_value'] ?? 'not set') . PHP_EOL;
echo 'Log Level: ' . (\$config['xdebug.log_level']['global_value'] ?? 'not set') . PHP_EOL;
"

# Test xDebug functions
echo "📋 Testing xDebug functions..."
docker-compose exec wordpress php -r "
if (function_exists('xdebug_info')) {
    echo '✅ xdebug_info() function available' . PHP_EOL;
} else {
    echo '❌ xdebug_info() function not available' . PHP_EOL;
}

if (function_exists('xdebug_break')) {
    echo '✅ xdebug_break() function available' . PHP_EOL;
} else {
    echo '❌ xdebug_break() function not available' . PHP_EOL;
}
"

# Check if profiling is enabled
echo "📋 Checking profiling configuration..."
docker-compose exec wordpress php -r "
\$profiler_enabled = ini_get('xdebug.profiler_enable');
\$profiler_trigger = ini_get('xdebug.profiler_enable_trigger');
echo 'Profiler enabled: ' . (\$profiler_enabled ? 'Yes' : 'No') . PHP_EOL;
echo 'Profiler trigger: ' . (\$profiler_trigger ? 'Yes' : 'No') . PHP_EOL;
"

# Test coverage functionality
echo "📋 Testing coverage functionality..."
docker-compose exec wordpress php -r "
if (function_exists('xdebug_start_code_coverage')) {
    echo '✅ Code coverage functions available' . PHP_EOL;
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    echo 'Coverage started successfully' . PHP_EOL;
    xdebug_stop_code_coverage();
    echo 'Coverage stopped successfully' . PHP_EOL;
} else {
    echo '❌ Code coverage functions not available' . PHP_EOL;
}
"

echo "🎉 xDebug test completed!"
echo ""
echo "💡 To start debugging:"
echo "   1. Set up your IDE to listen on port 9003"
echo "   2. Set IDE key to 'docker'"
echo "   3. Add breakpoints in your code"
echo "   4. Visit http://localhost:8080"
echo ""
echo "💡 To enable profiling:"
echo "   Add ?XDEBUG_PROFILE=1 to any URL"
echo ""
echo "💡 To view logs:"
echo "   tail -f xdebug-logs/xdebug.log"
