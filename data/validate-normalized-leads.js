const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'verified-leads-normalized.json');
const leads = JSON.parse(fs.readFileSync(file, 'utf8'));

const allowedBusinessTypes = new Set([
  'tienda_celulares_financia',
  'financiera_celulares',
  'electrodomesticos_financia_celulares',
  'bnpl_smartphones',
  'telecom_financia_celulares'
]);
const allowedLanguages = new Set(['es', 'en', 'other']);
const allowedFinancingStatus = new Set(['verified', 'probable', 'unknown', 'not_financing']);
const allowedStatuses = new Set(['new', 'verified', 'contacted', 'interested', 'demo', 'won', 'lost']);
const required = ['company_name', 'country', 'business_type', 'language', 'financing_status', 'source', 'source_date', 'score', 'status'];

const errors = [];
if (!Array.isArray(leads)) errors.push('Dataset must be an array.');

(leads || []).forEach((lead, i) => {
  const p = `lead[${i}]`;
  required.forEach(k => {
    if (lead[k] === undefined || lead[k] === null || lead[k] === '') errors.push(`${p}: missing ${k}`);
  });
  if (typeof lead.company_name !== 'string') errors.push(`${p}: company_name must be string`);
  if (typeof lead.country !== 'string') errors.push(`${p}: country must be string`);
  if (!allowedBusinessTypes.has(lead.business_type)) errors.push(`${p}: invalid business_type`);
  if (!allowedLanguages.has(lead.language)) errors.push(`${p}: invalid language`);
  if (!allowedFinancingStatus.has(lead.financing_status)) errors.push(`${p}: invalid financing_status`);
  if (!allowedStatuses.has(lead.status)) errors.push(`${p}: invalid status`);
  if (!Number.isInteger(lead.score) || lead.score < 0 || lead.score > 100) errors.push(`${p}: score must be integer 0-100`);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(lead.source_date)) errors.push(`${p}: source_date must be YYYY-MM-DD`);
  if (lead.store_count !== null && lead.store_count !== undefined && (!Number.isInteger(lead.store_count) || lead.store_count < 0)) errors.push(`${p}: invalid store_count`);
  if (lead.phone_brands !== undefined && !Array.isArray(lead.phone_brands)) errors.push(`${p}: phone_brands must be array`);
  if (lead.website !== undefined && lead.website !== '' && !/^https?:\/\//i.test(lead.website)) errors.push(`${p}: website must use http/https`);
  if (lead.financing_status === 'verified' && (!lead.financing_evidence || !lead.source)) errors.push(`${p}: verified financing requires evidence and source`);
});

const seen = new Set();
(leads || []).forEach((lead, i) => {
  const key = `${String(lead.company_name || '').trim().toLowerCase()}|${String(lead.country || '').trim().toLowerCase()}`;
  if (seen.has(key)) errors.push(`lead[${i}]: duplicate company/country`);
  seen.add(key);
});

if (errors.length) {
  console.error(`VALIDATION FAILED: ${errors.length} error(s)`);
  errors.forEach(e => console.error(`- ${e}`));
  process.exit(1);
}

console.log(`VALIDATION PASSED: ${leads.length} normalized lead(s) comply with required schema constraints.`);
