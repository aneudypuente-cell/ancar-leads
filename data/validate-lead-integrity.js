const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'verified-leads-normalized.json');
const leads = JSON.parse(fs.readFileSync(file, 'utf8'));
const errors = [];
const seen = new Map();
const allowedStatuses = new Set(['new', 'verified', 'contacted', 'qualified', 'converted', 'rejected']);
const allowedFinancingStatuses = new Set(['verified', 'unverified', 'pending']);

if (!Array.isArray(leads)) {
  console.error('INTEGRITY VALIDATION FAILED: dataset must be an array.');
  process.exit(1);
}

function normalize(value) {
  return String(value || '').trim().toLowerCase();
}

leads.forEach((lead, i) => {
  const name = String(lead.company_name || '').trim();
  const key = `${normalize(name)}|${normalize(lead.country)}`;
  const p = `lead[${i}] ${name || '(unnamed)'}`;

  if (!name) errors.push(`${p}: company_name is required`);
  if (!String(lead.country || '').trim()) errors.push(`${p}: country is required`);
  if (!String(lead.business_type || '').trim()) errors.push(`${p}: business_type is required`);
  if (!allowedStatuses.has(normalize(lead.status))) errors.push(`${p}: invalid status`);
  if (!allowedFinancingStatuses.has(normalize(lead.financing_status))) errors.push(`${p}: invalid financing_status`);
  if (!Number.isInteger(lead.score) || lead.score < 0 || lead.score > 100) errors.push(`${p}: score must be an integer from 0 to 100`);

  if (lead.source_date && !/^\d{4}-\d{2}-\d{2}$/.test(String(lead.source_date))) {
    errors.push(`${p}: source_date must use YYYY-MM-DD`);
  }

  if (seen.has(key)) {
    errors.push(`${p}: duplicate company_name + country; first occurrence is lead[${seen.get(key)}]`);
  } else if (name) {
    seen.set(key, i);
  }
});

if (errors.length) {
  console.error(`INTEGRITY VALIDATION FAILED: ${errors.length} error(s)`);
  errors.forEach(e => console.error(`- ${e}`));
  process.exit(1);
}

console.log(`INTEGRITY VALIDATION PASSED: ${leads.length} lead(s) have valid identity, status, score and uniqueness constraints.`);
