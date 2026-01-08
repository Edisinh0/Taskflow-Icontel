#!/bin/bash

# Test script for Case Closure Request API
# Usage: ./test-closure-endpoints.sh

echo "=== Case Closure Request API - Integration Test ==="
echo ""

# Base URL
BASE_URL="http://localhost:8000/api/v1"

# Get authentication tokens
echo "🔑 Getting authentication tokens..."

# Token for regular user (jramirez)
USER_TOKEN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"jramirez@icontel.cl","password":"password"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

# Token for SAC jefe (María José Araneda)
JEFE_TOKEN=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"maria.araneda@icontel.cl","password":"password123"}' \
  | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$USER_TOKEN" ]; then
  echo "❌ Failed to get user token"
  exit 1
fi

if [ -z "$JEFE_TOKEN" ]; then
  echo "❌ Failed to get jefe token"
  exit 1
fi

echo "✅ User token: ${USER_TOKEN:0:20}..."
echo "✅ Jefe token: ${JEFE_TOKEN:0:20}..."
echo ""

# Test 1: Create closure request
echo "📝 Test 1: Create closure request (as regular user)"
CASE_ID=1

RESPONSE=$(curl -s -X POST "$BASE_URL/cases/$CASE_ID/request-closure" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reason": "Cliente confirmó que el problema fue resuelto satisfactoriamente",
    "completion_percentage": 100
  }')

echo "Response:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"

REQUEST_ID=$(echo "$RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Request ID: $REQUEST_ID"
echo ""

# Test 2: List pending requests (as jefe)
echo "📋 Test 2: List pending closure requests (as SAC jefe)"

RESPONSE=$(curl -s -X GET "$BASE_URL/closure-requests?status=pending" \
  -H "Authorization: Bearer $JEFE_TOKEN")

echo "Response:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
echo ""

# Test 3: View request detail
if [ ! -z "$REQUEST_ID" ]; then
  echo "📄 Test 3: View closure request detail"
  
  RESPONSE=$(curl -s -X GET "$BASE_URL/closure-requests/$REQUEST_ID" \
    -H "Authorization: Bearer $JEFE_TOKEN")
  
  echo "Response:"
  echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
  echo ""

  # Test 4: Approve request
  echo "✅ Test 4: Approve closure request (as jefe)"
  
  RESPONSE=$(curl -s -X POST "$BASE_URL/closure-requests/$REQUEST_ID/approve" \
    -H "Authorization: Bearer $JEFE_TOKEN" \
    -H "Content-Type: application/json")
  
  echo "Response:"
  echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
  echo ""

  # Test 5: Verify case is closed
  echo "🔍 Test 5: Check case closure status"
  
  RESPONSE=$(curl -s -X GET "$BASE_URL/cases/$CASE_ID/closure-request" \
    -H "Authorization: Bearer $USER_TOKEN")
  
  echo "Response:"
  echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
fi

echo ""
echo "✅ API tests completed!"
