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

leads.forEach((lead, i) => {
  const p = `lead[${i}] ${lead.company_name || '(unnamed)'}`;
  if (lead.financing_status === 'verified') {
    if (!String(lead.financing_evidence || '').trim()) errors.push(`${p}: verified lead missing financing_evidence`);
    if (!String(lead.website || '').trim()) errors.push(`${p}: verified lead missing website`);
    if (!String(lead.financing_method || '').trim()) errors.push(`${p}: verified lead missing financing_method`);
    if (!String(lead.commercial_angle || '').trim()) warnings.push(`${p}: missing commercial_angle`);
  }

  if (String(lead.website || '').trim() && !/^https?:\/\//i.test(lead.website)) {
    errors.push(`${p}: website must use http/https`);
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
