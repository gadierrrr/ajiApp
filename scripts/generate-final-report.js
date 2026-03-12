#!/usr/bin/env node
/**
 * Generate comprehensive final audit report
 */

const fs = require('fs');
const path = require('path');

const RESULTS_DIR = path.join(__dirname, '../audit-results');
const TIMESTAMP = '2026-02-03T11-15-49';

// Read all JSON reports
const files = fs.readdirSync(RESULTS_DIR)
    .filter(f => f.startsWith(TIMESTAMP) && f.endsWith('.json') && !f.includes('summary') && !f.includes('contrast'));

console.log(`\n📊 Generating Final Comprehensive Audit Report\n`);
console.log(`Found ${files.length} audit reports\n`);

// Collect all scores
const allResults = [];

for (const file of files) {
    const filePath = path.join(RESULTS_DIR, file);
    const report = JSON.parse(fs.readFileSync(filePath, 'utf8'));

    if (!report.categories) continue;

    const url = report.finalUrl || report.requestedUrl || 'unknown';
    const urlPath = url.replace('https://www.puertoricobeachfinder.com', '').replace('https://www.aji.app', '');

    allResults.push({
        url: urlPath,
        fullUrl: url,
        performance: Math.round((report.categories.performance?.score || 0) * 100),
        accessibility: Math.round((report.categories.accessibility?.score || 0) * 100),
        bestPractices: Math.round((report.categories['best-practices']?.score || 0) * 100),
        seo: Math.round((report.categories.seo?.score || 0) * 100)
    });
}

// Read contrast report
const contrastReportPath = path.join(RESULTS_DIR, `${TIMESTAMP}_contrast-report.json`);
const contrastReport = JSON.parse(fs.readFileSync(contrastReportPath, 'utf8'));

// Calculate statistics
const stats = {
    totalPages: allResults.length,
    averages: {
        performance: Math.round(allResults.reduce((sum, r) => sum + r.performance, 0) / allResults.length),
        accessibility: Math.round(allResults.reduce((sum, r) => sum + r.accessibility, 0) / allResults.length),
        bestPractices: Math.round(allResults.reduce((sum, r) => sum + r.bestPractices, 0) / allResults.length),
        seo: Math.round(allResults.reduce((sum, r) => sum + r.seo, 0) / allResults.length)
    },
    distribution: {
        performance: {
            excellent: allResults.filter(r => r.performance >= 90).length,
            good: allResults.filter(r => r.performance >= 70 && r.performance < 90).length,
            needsWork: allResults.filter(r => r.performance >= 50 && r.performance < 70).length,
            poor: allResults.filter(r => r.performance < 50).length
        },
        accessibility: {
            excellent: allResults.filter(r => r.accessibility >= 90).length,
            good: allResults.filter(r => r.accessibility >= 80 && r.accessibility < 90).length,
            needsWork: allResults.filter(r => r.accessibility >= 70 && r.accessibility < 80).length,
            poor: allResults.filter(r => r.accessibility < 70).length
        }
    },
    contrast: {
        totalIssues: contrastReport.totalIssues,
        pagesWithIssues: contrastReport.pagesWithIssues,
        pagesClean: allResults.length - contrastReport.pagesWithIssues
    }
};

// Identify top performers and areas needing work
const topPerformers = allResults
    .filter(r => r.performance >= 90)
    .sort((a, b) => b.performance - a.performance)
    .slice(0, 5);

const performanceIssues = allResults
    .filter(r => r.performance < 60)
    .sort((a, b) => a.performance - b.performance);

const accessibilityIssues = allResults
    .filter(r => r.accessibility < 90)
    .sort((a, b) => a.accessibility - b.accessibility);

// Generate markdown report
const markdown = `# AJI - Comprehensive Audit Report

**Generated:** ${new Date().toLocaleString()}
**Total Pages Audited:** ${stats.totalPages}

---

## Executive Summary

### Overall Scores

| Category | Average Score | Grade |
|----------|--------------|-------|
| **Performance** | ${stats.averages.performance}/100 | ${getGrade(stats.averages.performance)} |
| **Accessibility** | ${stats.averages.accessibility}/100 | ${getGrade(stats.averages.accessibility)} |
| **Best Practices** | ${stats.averages.bestPractices}/100 | ${getGrade(stats.averages.bestPractices)} |
| **SEO** | ${stats.averages.seo}/100 | ${getGrade(stats.averages.seo)} |

### Score Distribution

#### Performance
- 🟢 Excellent (90-100): **${stats.distribution.performance.excellent}** pages
- 🟡 Good (70-89): **${stats.distribution.performance.good}** pages
- 🟠 Needs Work (50-69): **${stats.distribution.performance.needsWork}** pages
- 🔴 Poor (<50): **${stats.distribution.performance.poor}** pages

#### Accessibility
- 🟢 Excellent (90-100): **${stats.distribution.accessibility.excellent}** pages
- 🟡 Good (80-89): **${stats.distribution.accessibility.good}** pages
- 🟠 Needs Work (70-79): **${stats.distribution.accessibility.needsWork}** pages
- 🔴 Poor (<70): **${stats.distribution.accessibility.poor}** pages

### Color Contrast Issues

- **Total violations:** ${stats.contrast.totalIssues}
- **Pages with issues:** ${stats.contrast.pagesWithIssues}
- **Pages passing:** ${stats.contrast.pagesClean}

---

## Top Performing Pages

${topPerformers.map((page, idx) =>
`${idx + 1}. **${page.url}**
   - Performance: ${page.performance} | Accessibility: ${page.accessibility} | SEO: ${page.seo}`
).join('\n')}

---

## Critical Issues

### Performance Issues (Score < 60)

${performanceIssues.length > 0 ? performanceIssues.map(page =>
`- **${page.url}** - Score: ${page.performance}/100`
).join('\n') : '_No critical performance issues found_'}

### Accessibility Issues (Score < 90)

${accessibilityIssues.length > 0 ? accessibilityIssues.slice(0, 10).map(page =>
`- **${page.url}** - Score: ${page.accessibility}/100`
).join('\n') : '_All pages have excellent accessibility scores_'}

${accessibilityIssues.length > 10 ? `\n_...and ${accessibilityIssues.length - 10} more pages_` : ''}

---

## Common Color Contrast Violations

Based on analysis of ${contrastReport.pagesWithIssues} pages with contrast issues:

### Most Common Issues

1. **\`text-gray-500\` on dark backgrounds** (Contrast ratio: 2.99-3.44)
   - Foreground: #6b7280
   - Backgrounds: #1a2c32, #1c2128, #132024
   - **Fix:** Use \`text-gray-400\` or lighter shade for better contrast

2. **MapLibre attribution text** (Contrast ratio: 1.09)
   - Light gray text on white background
   - **Fix:** Adjust map control styling in CSS

3. **Small text (text-xs) with insufficient contrast**
   - Multiple instances of 12px text with gray-500
   - **Fix:** Increase text size or use darker color

### Affected Components

- Beach detail cards (\`.beach-detail-card\`)
- Quick fact cards (\`.quick-fact-card\`)
- Section headers and labels
- Map attribution controls
- Review and photo sections

---

## Recommendations

### High Priority

1. **Fix color contrast violations**
   - Replace \`text-gray-500\` with \`text-gray-400\` on dark backgrounds
   - Update CSS variables in \`public/assets/css/partials/_variables.css\`
   - Audit all \`.text-xs\` and \`.text-sm\` classes

2. **Optimize performance on municipality pages**
   - Current average: ${Math.round(allResults.filter(r => r.url.includes('municipality')).reduce((sum, r) => sum + r.performance, 0) / allResults.filter(r => r.url.includes('municipality')).length)}/100
   - Implement lazy loading for beach cards
   - Optimize image loading
   - Defer non-critical JavaScript

3. **Improve SEO on auth pages**
   - Login, profile, favorites pages have low SEO scores (58)
   - Add proper meta descriptions
   - Implement noindex for private pages

### Medium Priority

4. **Audit beach detail pages**
   - Beach pages audited: ${allResults.filter(r => r.url.includes('beach.php?slug=')).length}
   - Average accessibility: ${Math.round(allResults.filter(r => r.url.includes('beach.php?slug=')).reduce((sum, r) => sum + r.accessibility, 0) / allResults.filter(r => r.url.includes('beach.php?slug=')).length)}/100
   - Focus on consistent contrast across all beach pages

5. **Collection page performance**
   - Average score: ${Math.round(allResults.filter(r => r.url.includes('beaches') || r.url.includes('best-')).reduce((sum, r) => sum + r.performance, 0) / allResults.filter(r => r.url.includes('beaches') || r.url.includes('best-')).length)}/100
   - Optimize image delivery
   - Implement intersection observer for lazy loading

### Low Priority

6. **Maintain excellent best practices score**
   - Current average: ${stats.averages.bestPractices}/100 ✅
   - Keep HTTPS, secure headers, and console errors minimal

---

## Detailed Results

### All Pages by Category

${allResults.sort((a, b) => a.url.localeCompare(b.url)).map(page =>
`- **${page.url}**
  - Performance: ${page.performance} | Accessibility: ${page.accessibility} | Best Practices: ${page.bestPractices} | SEO: ${page.seo}`
).join('\n')}

---

## Files Generated

- **Full summary JSON:** \`audit-results/${TIMESTAMP}_summary.json\`
- **Scores CSV:** \`audit-results/${TIMESTAMP}_scores.csv\`
- **Contrast report:** \`audit-results/${TIMESTAMP}_contrast-report.json\`
- **Contrast issues CSV:** \`audit-results/${TIMESTAMP}_contrast-issues.csv\`
- **This report:** \`audit-results/${TIMESTAMP}_FINAL-REPORT.md\`

---

**Next Steps:**

1. Review contrast violations in \`${TIMESTAMP}_contrast-issues.csv\`
2. Update color variables in CSS partials
3. Re-run audit after fixes to verify improvements
4. Consider automated Lighthouse CI for continuous monitoring
`;

// Write markdown report
const reportPath = path.join(RESULTS_DIR, `${TIMESTAMP}_FINAL-REPORT.md`);
fs.writeFileSync(reportPath, markdown);

console.log('✅ Final report generated!\n');
console.log('📄 Report saved to:', reportPath);
console.log('\n' + '='.repeat(80));
console.log(markdown.split('\n').slice(0, 50).join('\n'));
console.log('...\n(See full report at ' + reportPath + ')');
console.log('='.repeat(80) + '\n');

function getGrade(score) {
    if (score >= 90) return '🟢 A';
    if (score >= 80) return '🟡 B';
    if (score >= 70) return '🟠 C';
    if (score >= 60) return '🔴 D';
    return '❌ F';
}
