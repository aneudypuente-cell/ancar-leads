const segments={
  tienda_celulares_financia:{es:['celulares financiados','celulares a crédito','celulares en cuotas','teléfonos a crédito','smartphones financiados','tienda de celulares financiamiento'],en:['phones on finance','smartphones on finance','phone financing','mobile phone financing','smartphones on installments','phone store financing']},
  financiera_celulares:{es:['financiera de celulares','crédito para celulares','financiamiento de smartphones','crédito para teléfonos','financiamiento de dispositivos móviles'],en:['smartphone financing company','mobile phone finance company','phone financing company','device financing','mobile device financing']},
  electrodomesticos_financia_celulares:{es:['electrodomésticos celulares a crédito','tienda de electrónica financiamiento celulares','electrónica celulares financiados','tienda de electrónica cuotas celulares'],en:['electronics store phone financing','electronics retailer smartphone financing','appliance store smartphone financing','electronics store installments phones']},
  bnpl_smartphones:{es:['BNPL celulares','compra ahora paga después celulares','celulares buy now pay later'],en:['BNPL smartphones','buy now pay later phones','smartphone BNPL','phone installment financing']},
  telecom_financia_celulares:{es:['operador celular financia smartphones','telco celulares a cuotas','telecom celulares financiados'],en:['telecom smartphone financing','mobile operator phone installments','carrier smartphone financing']}
};
const regions={global:['global'],latin_america:['Mexico','Brazil','Colombia','Peru','Ecuador','Chile','Argentina','Dominican Republic','Guatemala','Costa Rica','Panama','Honduras','El Salvador','Bolivia','Paraguay','Uruguay'],north_america:['United States','Canada','Mexico'],europe:['United Kingdom','Spain','Germany','France','Italy','Portugal','Netherlands','Belgium','Ireland','Poland','Romania','Sweden','Norway'],africa:['Kenya','Nigeria','Ghana','Uganda','Tanzania','South Africa','Rwanda','Zambia','Ethiopia','Egypt'],asia:['India','Philippines','Indonesia','Malaysia','Thailand','Vietnam','Bangladesh','Pakistan','Japan','South Korea'],middle_east:['United Arab Emirates','Saudi Arabia','Jordan','Israel','Turkey','Qatar'],oceania:['Australia','New Zealand']};
const $=id=>document.getElementById(id);
let generated=[];
function build(){
  const segment=$('segment').value, language=$('language').value, region=$('country').value, extra=$('extra').value.trim();
  const chosen=segment==='all'?Object.keys(segments):[segment];
  const langs=language==='both'?['es','en']:[language];
  const places=regions[region]||['global'];
  generated=[];
  for(const s of chosen) for(const lang of langs) for(const place of places){
    for(const term of segments[s][lang]) generated.push({segment:s,language:lang,place,query:`"${term}" ${place==='global'?'':`"${place}" `}${extra}`.trim()});
  }
  generated=[...new Map(generated.map(x=>[x.query,x])).values()];
  $('queryCount').textContent=generated.length;
  $('queries').innerHTML=generated.slice(0,120).map((x,i)=>`<div class="query"><small>${i+1} · ${x.segment} · ${x.language.toUpperCase()} · ${x.place}</small><code>${escapeHtml(x.query)}</code></div>`).join('') || '<p>No hay consultas para los filtros seleccionados.</p>';
}
function escapeHtml(v){return v.replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function csv(){if(!generated.length)build();const rows=[['segment','language','place','query'],...generated.map(x=>[x.segment,x.language,x.place,x.query])];const text=rows.map(r=>r.map(v=>'"'+String(v).replaceAll('"','""')+'"').join(',')).join('\n');const a=document.createElement('a');a.href=URL.createObjectURL(new Blob([text],{type:'text/csv;charset=utf-8'}));a.download='ancar-global-search-queries.csv';a.click();URL.revokeObjectURL(a.href)}
$('build').addEventListener('click',build);$('export').addEventListener('click',csv);build();
