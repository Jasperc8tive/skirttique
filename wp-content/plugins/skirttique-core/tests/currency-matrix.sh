#!/usr/bin/env bash
#
# Live multi-currency integration check.
#
# Proves the full chain the unit tests can't reach on their own:
# market cookie -> Market::current() -> Currency price filters ->
# WooCommerce price HTML, rendered by the real theme on the running site.
#
# Asserts, per market: the correct currency symbol appears AND the amount
# is a whole number (decimals=0). Robust to rate changes — it checks the
# money VOICE, not a frozen figure. Run against a warmed dev site:
#
#   bash tests/currency-matrix.sh [base-url] [product-slug]
#
set -u

BASE="${1:-http://127.0.0.1:8888}"
SLUG="${2:-adaeze-silk-maxi}"
URL="$BASE/product/$SLUG/"

# market:symbol pairs.
declare -a MARKETS=("NG:₦" "US:$" "GB:£" "ZA:R" "AE:د.إ")

pass=0
fail=0

extract() {
	PYTHONIOENCODING=utf-8 python -c "import sys,io,html,re; sys.stdout=io.TextIOWrapper(sys.stdout.buffer,encoding='utf-8'); t=sys.stdin.read(); m=re.search(r'st-buy__price\"[^>]*>(.*?)</p>', t, re.S); print(html.unescape(re.sub(r'<[^>]+>','',m.group(1))).strip() if m else '')"
}

for pair in "${MARKETS[@]}"; do
	code="${pair%%:*}"
	symbol="${pair#*:}"
	price="$(curl -s --cookie "skirttique_market=$code" "$URL" | extract)"

	# Digits only, stripped of symbol and separators.
	digits="$(printf '%s' "$price" | tr -cd '0-9')"
	has_decimal="$(printf '%s' "$price" | grep -qE '[.][0-9]' && echo yes || echo no)"

	if [[ "$price" == *"$symbol"* && -n "$digits" && "$has_decimal" == "no" ]]; then
		echo "PASS  $code  $price"
		pass=$((pass + 1))
	else
		echo "FAIL  $code  '$price'  (expected symbol '$symbol', whole number)"
		fail=$((fail + 1))
	fi
done

echo
echo "matrix: $pass passed, $fail failed"
[[ "$fail" -eq 0 ]]
