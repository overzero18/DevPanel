#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEVPANEL_TEST_PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
PORT="${DEVPANEL_PORT:-80}"

if [[ -z "$DEVPANEL_TEST_PASSWORD" ]]; then
    echo "❌ DEVPANEL_TEST_PASSWORD not set"
    exit 1
fi

BASE_URL="http://localhost:${PORT}/devpanel"

test_api_endpoint() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local expected_status="$4"

    echo -n "Testing: $method $endpoint ... "

    local cmd="curl -s -w '\n%{http_code}' -X $method"

    if [[ "$method" == "POST" ]]; then
        cmd="$cmd -H 'Content-Type: application/json' -d '$data'"
    fi

    cmd="$cmd '$BASE_URL/$endpoint'"

    local output
    output=$($cmd) || true

    local response
    local status
    response=$(echo "$output" | head -n -1)
    status=$(echo "$output" | tail -n 1)

    if [[ "$status" == "$expected_status" ]]; then
        echo "✓ ($status)"
        return 0
    else
        echo "❌ Expected $expected_status, got $status"
        echo "Response: $response"
        return 1
    fi
}

login_and_get_session() {
    echo -n "Logging in ... "

    local login_response
    login_response=$(curl -s -c /tmp/devpanel_cookies.txt -X POST \
        -H 'Content-Type: application/x-www-form-urlencoded' \
        -d "password=$DEVPANEL_TEST_PASSWORD" \
        "$BASE_URL/api/auth/login.php")

    if echo "$login_response" | grep -q '"success":true'; then
        echo "✓"
        return 0
    else
        echo "❌"
        return 1
    fi
}

test_authenticated_endpoint() {
    local method="$1"
    local endpoint="$2"
    local data="$3"
    local expected_status="$4"

    echo -n "Testing: $method $endpoint ... "

    local cmd="curl -s -w '\n%{http_code}' -b /tmp/devpanel_cookies.txt -X $method"

    if [[ "$method" == "POST" ]]; then
        cmd="$cmd -H 'Content-Type: application/json' -d '$data'"
    fi

    cmd="$cmd '$BASE_URL/$endpoint'"

    local output
    output=$($cmd) || true

    local response
    local status
    response=$(echo "$output" | head -n -1)
    status=$(echo "$output" | tail -n 1)

    if [[ "$status" == "$expected_status" ]]; then
        echo "✓ ($status)"
        return 0
    else
        echo "❌ Expected $expected_status, got $status"
        return 1
    fi
}

echo "🧪 Running extended functional smoke tests..."
echo ""

# Setup
login_and_get_session

echo ""
echo "📦 Testing Plugin System..."
test_authenticated_endpoint "GET" "api/plugins/list.php" "" "200"

echo ""
echo "🛒 Testing Marketplace..."
test_authenticated_endpoint "GET" "api/marketplace/list.php" "" "200"

echo ""
echo "📚 Testing Releases..."
test_authenticated_endpoint "GET" "api/releases/list.php" "" "200"

echo ""
echo "🔄 Testing Updater..."
test_authenticated_endpoint "GET" "api/updater/history.php" "" "200"

echo ""
echo "📝 Testing New Pages..."
test_authenticated_endpoint "GET" "releases.php" "" "200"
test_authenticated_endpoint "GET" "ci.php" "" "200"

echo ""
echo "✅ All extended functional tests completed"

rm -f /tmp/devpanel_cookies.txt
