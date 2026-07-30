const fs = require('fs');
const path = require('path');
const xlsx = require('xlsx');

const dir = 'dataset_alat_icadoang/CEKLIST SURGICAL INSTRUMENT FIX-20260730T050606Z-1-001/CEKLIST SURGICAL INSTRUMENT FIX';

const files = fs.readdirSync(dir).filter(f => f.endsWith('.xlsx') || f.endsWith('.xls'));

const sets = [];

for (const file of files) {
    const filePath = path.join(dir, file);
    let wb;
    try {
        wb = xlsx.readFile(filePath);
    } catch (e) {
        console.error('Error reading', file, e.message);
        continue;
    }
    
    const sheetName = wb.SheetNames[0];
    // header: 1 returns array of arrays
    const rawData = xlsx.utils.sheet_to_json(wb.Sheets[sheetName], {header: 1, defval: ''});
    
    let setName = file.replace(/\.xlsx?$/, '').trim();
    let setCode = 'SET-' + setName.toUpperCase().replace(/[^A-Z0-9]/g, '-').replace(/-+/g, '-').substring(0, 20);
    if (setCode.endsWith('-')) setCode = setCode.slice(0, -1);
    
    // Find header row
    let headerRowIndex = -1;
    let codeCol = -1;
    let qtyCol = -1;
    let nameCol = -1;
    
    for (let i = 0; i < Math.min(rawData.length, 30); i++) {
        const row = rawData[i];
        if (!row || !Array.isArray(row)) continue;
        
        const rowStr = row.map(c => String(c).toLowerCase()).join('|');
        if ((rowStr.includes('items') || rowStr.includes('nama alat')) && (rowStr.includes('qty') || rowStr.includes('jumlah'))) {
            headerRowIndex = i;
            // find exact column indices
            for (let j = 0; j < row.length; j++) {
                const cell = String(row[j]).toLowerCase();
                if (cell.includes('code') || cell.includes('kode')) codeCol = j;
                if (cell.includes('qty') || cell.includes('jumlah')) qtyCol = j;
                if (cell.includes('items') || cell.includes('nama')) nameCol = j;
            }
            break;
        }
    }
    
    const items = [];
    if (headerRowIndex !== -1 && codeCol !== -1 && qtyCol !== -1) {
        for (let i = headerRowIndex + 1; i < rawData.length; i++) {
            const row = rawData[i];
            if (!row || row.length === 0) continue;
            
            const code = String(row[codeCol] || '').trim();
            const qtyStr = String(row[qtyCol] || '').trim();
            
            if (code && code.length > 2 && qtyStr) {
                // parse qty
                const qtyMatch = qtyStr.match(/(\d+)/);
                const qty = qtyMatch ? parseInt(qtyMatch[1], 10) : 1;
                
                items.push({
                    code: code,
                    qty: qty
                });
            }
        }
    } else {
        console.warn('Could not find proper headers in', file);
    }
    
    if (items.length > 0) {
        sets.push({
            code: setCode,
            name: setName,
            description: `Kompilasi otomatis dari ${file}`,
            items: items
        });
    }
}

fs.writeFileSync('database/data/real_sets.json', JSON.stringify(sets, null, 2));
console.log(`Extracted ${sets.length} sets from ${files.length} files.`);
