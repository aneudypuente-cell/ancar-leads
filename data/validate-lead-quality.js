const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'verified-leads-normalized.json');
const leads = JSON.parse(fs.readFileSync(file, 'utf8'));
const errors = [];
const warnings = [];

if (!Array.isArray(leads)) {
  console.error('QUALITY VALIDATION FAILED: dataset must be an array.');
  process.exit(1);
}

function validHttpUrl(value) {
  try {
    const url = new URL(String(value || '').trim());
    return ['http:', 'https:'].includes(url.protocol) && Boolean(url.hostname);
  } catch {
    return false;
  }
}

leads.forEach((lead, i) => {
  const p = `lead[${i}] ${lead.company_name || '(unnamed)'}`;
  const website = String(lead.website || '').trim();
  const source = String(lead.source || '').trim();
  const evidence = String(lead.financing_evidence || '').trim();
  const method = String(lead.financing_method || '').trim();

  if (lead.financing_status === 'verified') {
    if (!evidence) errors.push(`${p}: verified lead missing financing_evidence`);
    if (!website) errors.push(`${p}: verified lead missing website`);
    if (!method) errors.push(`${p}: verified lead missing financing_method`);
    if (!source) errors.push(`${p}: verified lead missing source`);
    if (!validHttpUrl(website)) errors.push(`${p}: verified lead website must be a valid http/https URL`);
    if (!String(lead.commercial_angle || '').trim()) warnings.push(`${p}: missing commercial_angle`);
  }

  if (website && !validHttpUrl(website)) {
    errors.push(`${p}: website must be a valid http/https URL`);
  }

  if (lead.source_date && !/^\d{4}-\d{2}-\d{2}$/.test(lead.source_date)) {
    errors.push(`${p}: source_date must use YYYY-MM-DD`);
  }

  if (Number.isInteger(lead.score) && lead.score >= 80 && lead.status === 'new') {
    warnings.push(`${p}: high-score lead is still marked new; review for verification/outreach workflow`);
  }
});

if (warnings.length) {
  console.warn(`QUALITY WARNINGS: ${warnings.length}`);
  warnings.forEach(w => console.warn(`- ${w}`));
}

if (errors.length) {
  console.error(`QUALITY VALIDATION FAILED: ${errors.length} error(s)`);
  errors.forEach(e => console.error(`- ${e}`));
  process.exit(1);
}

console.log(`QUALITY VALIDATION PASSED: ${leads.length} lead(s) satisfy required quality fields.`);
