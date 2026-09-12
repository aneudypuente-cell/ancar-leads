const fs = require('fs');
const path = require('path');

const root = path.join(__dirname, '..');
const index = fs.readFileSync(path.join(root, 'index.html'), 'utf8');
const app = fs.readFileSync(path.join(root, 'app.js'), 'utf8');

const requiredHtmlIds = [
  'country', 'segment', 'language', 'extra', 'build', 'export',
  'queries', 'queryCount', 'seedLeads', 'leadFilter', 'leadCountry',
  'leadScore', 'leadSearch', 'clearLeadFilters', 'exportLeads', 'leadCount'
];

const missingIds = requiredHtmlIds.filter(id => !new RegExp(`id=["']${id}["']`).test(index));
if (missingIds.length) {
  throw new Error(`UI CONTRACT: missing HTML id(s): ${missingIds.join(', ')}`);
}

if (!/<script\s+src=["']app\.js["']\s*><\/script>/i.test(index)) {
  throw new Error('UI CONTRACT: index.html must load app.js');
}

const requiredFunctions = [
  'build', 'csv', 'loadSeeds', 'renderSeeds', 'exportLeads',
  'clearLeadFilters', 'normalizeLead', 'scoreLead'
];
const missingFunctions = requiredFunctions.filter(name => !new RegExp(`function\\s+${name}\\s*\\(`).test(app));
if (missingFunctions.length) {
  throw new Error(`UI CONTRACT: missing app function(s): ${missingFunctions.join(', ')}`);
}

if (!app.includes("data/verified-leads-normalized.json")) {
  throw new Error('UI CONTRACT: app.js must load the normalized leads dataset');
}

console.log(`UI CONTRACT PASSED: ${requiredHtmlIds.length} required controls and ${requiredFunctions.length} required functions are present.`);
