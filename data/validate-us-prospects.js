const fs = require('fs');
const path = require('path');
const leads = JSON.parse(fs.readFileSync(path.join(__dirname, 'us-prospects.json'), 'utf8'));
const types = new Set(['reparacion_celulares_consolas', 'tienda_electrodomesticos_financia', 'tienda_celulares_financia', 'financiera_celulares', 'electrodomesticos_financia_celulares', 'bnpl_smartphones', 'telecom_financia_celulares']);
const emailStatuses = new Set(['published_public', 'deliverability_unverified', 'not_published', 'pending_review']);
const errors = []; const seen = new Set();
if (!Array.isArray(leads)) errors.push('Dataset must be an array.');
(leads || []).forEach((x, i) => {
  const p = `lead[${i}]`;
  for (const k of ['company_name','country','business_type','language','financing_status','source','source_date','score','status']) if (x[k] === undefined || x[k] === null || x[k] === '') errors.push(`${p}: missing ${k}`);
  if (!types.has(x.business_type)) errors.push(`${p}: invalid business_type`);
  if (!['es','en','other'].includes(x.language)) errors.push(`${p}: invalid language`);
  if (!['verified','probable','unknown','not_financing'].includes(x.financing_status)) errors.push(`${p}: invalid financing_status`);
  if (!['new','verified','contacted','interested','demo','won','lost'].includes(x.status)) errors.push(`${p}: invalid status`);
  if (!Number.isInteger(x.score) || x.score < 0 || x.score > 100) errors.push(`${p}: invalid score`);
  if (!/^\d{4}-\d{2}-\d{2}$/.test(x.source_date)) errors.push(`${p}: invalid source_date`);
  if (!(String(x.source).startsWith('https://') || String(x.source).startsWith('http://'))) errors.push(`${p}: source must be URL`);
  if (x.email && (!String(x.email).includes('@') || !String(x.email).split('@')[1].includes('.'))) errors.push(`${p}: invalid email`);
  if (!emailStatuses.has(x.email_verification)) errors.push(`${p}: invalid email_verification`);
  if (x.email_verification === 'published_public' && !x.email) errors.push(`${p}: published email missing`);
  if (x.email && x.email_verification === 'published_public' && !(String(x.email_source || '').startsWith('https://') || String(x.email_source || '').startsWith('http://'))) errors.push(`${p}: email source missing`);
  const key = (x.company_name + '|' + x.country + '|' + (x.city || '')).toLowerCase().trim();
  if (seen.has(key)) errors.push(`${p}: duplicate company/location`); seen.add(key);
});
if (errors.length) { console.error(`VALIDATION FAILED: ${errors.length} errors`); errors.forEach(e => console.error('- ' + e)); process.exit(1); }
console.log(`VALIDATION PASSED: ${leads.length} US prospects. Public email does not imply deliverability verification.`);
