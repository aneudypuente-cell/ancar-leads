const fs = require('fs');
const path = require('path');

const file = path.join(__dirname, 'verified-leads-normalized.json');
const leads = JSON.parse(fs.readFileSync(file, 'utf8'));

function hasFinancingEvidence(x) {
  return /financ|credit|cr[eé]dit|cuot|installment|BNPL|loan|finance/i.test(`${x.financing_evidence || ''} ${x.financing_status || ''}`);
}
function hasPaymentTerms(x) {
  return /cuot|installment|BNPL|loan|weekly|monthly|daily|deposit|down payment|0%|months?|seman|mensual|diar|dep[oó]sito|inicial/i.test(`${x.financing_evidence || ''} ${x.financing_method || ''}`);
}
function hasNetwork(x) {
  return Number(x.store_count) > 1 || /partner network|dealer(?:s)? network|dealer(?:s)?|merchant(?:s)? network|sucursal(?:es)?|branch(?:es)?|locations?|red de (?:tiendas|distribuidores|sucursales)|agent(?:s)? network|multiple locations|nationwide network/i.test(`${x.financing_evidence || ''} ${x.commercial_angle || ''} ${x.notes || ''}`);
}
function isContactValue(value) {
  const raw = String(value || '').trim();
  return Boolean(raw) && (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(raw) || /\+?[0-9][0-9\s().-]{6,}/.test(raw) || /^https?:\/\//i.test(raw));
}
function hasPublicContact(x) {
  return [x.email, x.phone, x.contact_email, x.contact_phone, x.public_contact, x.contact].some(isContactValue);
}
function hasRecentSource(x) {
  if (!x.source || !x.source_date) return false;
  const d = Date.parse(x.source_date);
  return Number.isFinite(d) && (Date.now() - d) >= 0 && (Date.now() - d) <= 30 * 24 * 60 * 60 * 1000;
}
function scoreLead(x) {
  const score = (hasFinancingEvidence(x) ? 40 : 0)
    + (hasPaymentTerms(x) ? 20 : 0)
    + (hasNetwork(x) ? 15 : 0)
    + (hasPublicContact(x) ? 15 : 0)
    + (hasRecentSource(x) ? 10 : 0);
  return Math.min(score, 100);
}

if (!Array.isArray(leads)) throw new Error('Dataset must be an array');
const mismatches = [];
for (const [index, lead] of leads.entries()) {
  const expected = scoreLead(lead);
  const stored = Number(lead.score);
  if (!Number.isInteger(stored) || stored !== expected) {
    mismatches.push({ index: index + 1, company: lead.company_name || 'Sin nombre', stored, expected });
  }
}

if (mismatches.length) {
  console.error(`SCORE AUDIT: ${mismatches.length} lead(s) have stored scores different from the current application rules.`);
  for (const item of mismatches) console.error(`- ${item.company}: stored=${item.stored}, expected=${item.expected}`);
  process.exit(1);
}

console.log(`SCORE AUDIT PASSED: ${leads.length} lead(s) match the current scoring rules.`);
