#!/usr/bin/env node
/**
 * Comprehensive Lighthouse Audit Script
 * Audits all main pages, collection pages, and samples of beach/municipality pages
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://www.puertoricobeachfinder.com';
const RESULTS_DIR = path.join(__dirname, '../audit-results');
const TIMESTAMP = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);

// Create results directory
if (!fs.existsSync(RESULTS_DIR)) {
    fs.mkdirSync(RESULTS_DIR, { recursive: true });
}

// URLs to audit
const urls = {
    main: [
        '/',
        '/login.php',
        '/onboarding.php',
        '/profile.php',
        '/favorites.php',
        '/quiz.php',
        '/compare.php',
        '/offline.php',
        '/sitemap.php'
    ],
    collections: [
        '/best-beaches.php',
        '/best-beaches-san-juan.php',
        '/best-family-beaches.php',
        '/best-snorkeling-beaches.php',
        '/best-surfing-beaches.php',
        '/beaches-near-san-juan.php',
        '/beaches-near-san-juan-airport.php',
        '/hidden-beaches-puerto-rico.php'
    ],
    // Will be populated from database
    beaches: [],
    municipalities: []
};

// Get sample beaches from database
function getSampleBeaches() {
    try {
        const output = execSync(
            `sqlite3 /var/www/ajiApp/data/aji-app.db "SELECT slug FROM beaches WHERE rating >= 4.0 ORDER BY RANDOM() LIMIT 12"`,
            { encoding: 'utf8' }
        );
        return output.trim().split('\n').map(slug => `/beach.php?slug=${slug}`);
    } catch (error) {
        console.error('Error fetching beaches:', error.message);
        return [];
    }
}

// Get sample municipalities
function getSampleMunicipalities() {
    try {
        const output = execSync(
            `sqlite3 /var/www/ajiApp/data/aji-app.db "SELECT DISTINCT municipality FROM beaches ORDER BY RANDOM() LIMIT 6"`,
            { encoding: 'utf8' }
        );
        return output.trim().split('\n').map(muni => `/municipality.php?name=${encodeURIComponent(muni)}`);
    } catch (error) {
        console.error('Error fetching municipalities:', error.message);
        return [];
    }
}

// Populate dynamic URLs
urls.beaches = getSampleBeaches();
urls.municipalities = getSampleMunicipalities();

// Flatten all URLs
const allUrls = [
    ...urls.main,
    ...urls.collections,
    ...urls.beaches,
    ...urls.municipalities
];

console.log(`\n🔍 Starting Lighthouse Audit`);
console.log(`📊 Total pages to audit: ${allUrls.length}`);
console.log(`📁 Results directory: ${RESULTS_DIR}\n`);

const results = [];
let completed = 0;

// Run Lighthouse on each URL
for (const urlPath of allUrls) {
    completed++;
    const fullUrl = `${BASE_URL}${urlPath}`;
    const safeName = urlPath.replace(/[^a-zA-Z0-9-]/g, '_').replace(/_+/g, '_');
    const outputPath = path.join(RESULTS_DIR, `${TIMESTAMP}_${safeName}.json`);

    console.log(`[${completed}/${allUrls.length}] Auditing: ${urlPath}`);

    try {
        // Run Lighthouse with JSON output
        execSync(
            `lighthouse "${fullUrl}" \
                --output=json \
                --output-path="${outputPath}" \
                --chrome-flags="--headless --no-sandbox --disable-dev-shm-usage" \
                --quiet \
                --only-categories=performance,accessibility,best-practices,seo`,
            { encoding: 'utf8', stdio: 'pipe' }
        );

        // Read the results
        const report = JSON.parse(fs.readFileSync(outputPath, 'utf8'));

        const scores = {
            url: urlPath,
            performance: Math.round(report.categories.performance.score * 100),
            accessibility: Math.round(report.categories.accessibility.score * 100),
            bestPractices: Math.round(report.categories['best-practices'].score * 100),
            seo: Math.round(report.categories.seo.score * 100)
        };

        results.push(scores);

        console.log(`  ✓ Performance: ${scores.performance} | Accessibility: ${scores.accessibility} | Best Practices: ${scores.bestPractices} | SEO: ${scores.seo}`);

    } catch (error) {
        console.error(`  ✗ Failed: ${error.message}`);
        results.push({
            url: urlPath,
            performance: 0,
            accessibility: 0,
            bestPractices: 0,
            seo: 0,
            error: error.message
        });
    }
}

// Generate summary report
const summary = {
    timestamp: new Date().toISOString(),
    totalPages: allUrls.length,
    successful: results.filter(r => !r.error).length,
    failed: results.filter(r => r.error).length,
    averageScores: {
        performance: Math.round(results.reduce((sum, r) => sum + r.performance, 0) / results.length),
        accessibility: Math.round(results.reduce((sum, r) => sum + r.accessibility, 0) / results.length),
        bestPractices: Math.round(results.reduce((sum, r) => sum + r.bestPractices, 0) / results.length),
        seo: Math.round(results.reduce((sum, r) => sum + r.seo, 0) / results.length)
    },
    results: results
};

// Save summary as JSON
const summaryPath = path.join(RESULTS_DIR, `${TIMESTAMP}_summary.json`);
fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));

// Generate CSV
const csvPath = path.join(RESULTS_DIR, `${TIMESTAMP}_scores.csv`);
const csvHeader = 'URL,Performance,Accessibility,Best Practices,SEO,Status\n';
const csvRows = results.map(r =>
    `"${r.url}",${r.performance},${r.accessibility},${r.bestPractices},${r.seo},${r.error ? 'FAILED' : 'OK'}`
).join('\n');
fs.writeFileSync(csvPath, csvHeader + csvRows);

// Print summary
console.log('\n' + '='.repeat(80));
console.log('📊 AUDIT SUMMARY');
console.log('='.repeat(80));
console.log(`Total pages audited: ${summary.totalPages}`);
console.log(`Successful: ${summary.successful}`);
console.log(`Failed: ${summary.failed}`);
console.log('\nAverage Scores:');
console.log(`  Performance:     ${summary.averageScores.performance}/100`);
console.log(`  Accessibility:   ${summary.averageScores.accessibility}/100`);
console.log(`  Best Practices:  ${summary.averageScores.bestPractices}/100`);
console.log(`  SEO:             ${summary.averageScores.seo}/100`);
console.log('\nFiles generated:');
console.log(`  Summary: ${summaryPath}`);
console.log(`  CSV:     ${csvPath}`);
console.log(`  Details: ${RESULTS_DIR}/${TIMESTAMP}_*.json`);
console.log('='.repeat(80) + '\n');
