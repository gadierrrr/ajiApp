#!/bin/bash

# Beach detail pages audit
BEACHES=(
    "playa-brava"
    "playa-tamarindo-guayanilla-guayanilla-17975-66781"
    "playa-de-vega"
    "salinas-beach"
    "baha-de-toa-baja-shore-pr-165-toa-baja-18449-66241"
    "playa-el-convento"
    "maras-beach-rincn-18354-67267"
    "cibuco-mouth-west-side-vega-baja-18469-66434"
    "playa-de-jaboncillo"
    "barrerolos-almendros-rincn-18335-67246"
    "buy-south-point-cabo-rojo-18075-67193"
    "mosquito-bay-beach"
)

BASE_URL="https://www.puertoricobeachfinder.com"
RESULTS_DIR="/var/www/ajiApp/audit-results"
TIMESTAMP="2026-02-03T11-15-49"

echo "🏖️  Auditing ${#BEACHES[@]} beach detail pages..."
echo ""

for i in "${!BEACHES[@]}"; do
    slug="${BEACHES[$i]}"
    num=$((i + 1))
    url="${BASE_URL}/beach.php?slug=${slug}"
    safe_name=$(echo "beach_${slug}" | sed 's/[^a-zA-Z0-9-]/_/g' | sed 's/_\+/_/g')
    output_path="${RESULTS_DIR}/${TIMESTAMP}_${safe_name}.json"

    echo "[${num}/${#BEACHES[@]}] Auditing: /beach.php?slug=${slug}"

    lighthouse "${url}" \
        --output=json \
        --output-path="${output_path}" \
        --chrome-flags="--headless --no-sandbox --disable-dev-shm-usage" \
        --quiet \
        --only-categories=performance,accessibility,best-practices,seo 2>&1 | grep -E "Performance|Accessibility|Best" || true

    # Extract scores from the JSON
    if [ -f "${output_path}" ]; then
        perf=$(jq -r '.categories.performance.score * 100 | floor' "${output_path}")
        a11y=$(jq -r '.categories.accessibility.score * 100 | floor' "${output_path}")
        bp=$(jq -r '.categories["best-practices"].score * 100 | floor' "${output_path}")
        seo=$(jq -r '.categories.seo.score * 100 | floor' "${output_path}")
        echo "  ✓ Performance: ${perf} | Accessibility: ${a11y} | Best Practices: ${bp} | SEO: ${seo}"
    else
        echo "  ✗ Failed to generate report"
    fi
    echo ""
done

echo "✅ Beach detail pages audit complete!"
