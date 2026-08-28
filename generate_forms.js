const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, BorderStyle, WidthType, VerticalAlign, UnderlineType,
  ImageRun, LineRuleType
} = require('docx');
const fs = require('fs');
const path = require('path');

// ── Patient data from CLI or Temp JSON file ───────────────────
let rawData = '{}';
if (process.argv[2]) {
  try {
    // If a JSON file path is passed from PHP, read it; otherwise treat it as a raw string
    if (fs.existsSync(process.argv[2])) {
      rawData = fs.readFileSync(process.argv[2], 'utf8');
    } else {
      rawData = process.argv[2];
    }
  } catch (e) {
    rawData = process.argv[2];
  }
}

let parsedCLI = {};
try {
  parsedCLI = JSON.parse(rawData.trim());
} catch (e) {
  parsedCLI = {};
}

const data = Object.assign({
  patient_name: 'RUTHAMMA w/o Shri. YESUDASS',
  patient_age: '82',
  patient_gender: 'F',
  op_number: '6370',
  relationship: 'Wife',
  emp_number: 'Emp.No.620091',
  relhs_number: 'RE-001760',
  basic_pay: 'Rs. /-',
  provisional_diagnosis: '',
  pta_re: '',
  pta_le: '',
  date_of_last_procurement: 'NIL',
  recommendation: '',
  date: '16-05-2025',
}, parsedCLI);

// ── Load logos using reliable absolute mapping ────────────────
const g20Buffer      = fs.readFileSync(path.join(__dirname, 'g20_logo.png'));
const railwaysBuffer   = fs.readFileSync(path.join(__dirname, 'railways_logo.png'));

// ── Border helpers ────────────────────────────────────────────
const noBorder  = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const noBorders = { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder };
const botBorder = { style: BorderStyle.SINGLE, size: 4, color: '000000' };

function cell(children, opts = {}) {
  return new TableCell({
    borders:       opts.borders || noBorders,
    width:         opts.width   || undefined,
    verticalAlign: opts.valign  || VerticalAlign.TOP,
    columnSpan:    opts.colspan || 1,
    margins:       { top: 40, bottom: 40, left: 60, right: 60 },
    children:      Array.isArray(children) ? children : [children],
  });
}

function para(text, opts = {}) {
  const runs = Array.isArray(text) ? text : [
    new TextRun({
      text:      text || '',
      bold:      opts.bold      || false,
      underline: opts.underline ? { type: UnderlineType.SINGLE } : undefined,
      size:      opts.size      || 20,
      font:      opts.font      || 'Times New Roman',
      italics:   opts.italic    || false,
    })
  ];
  return new Paragraph({
    alignment: opts.align  || AlignmentType.LEFT,
    spacing: {
      before:   opts.before || 0,
      after:    opts.after  || 0,
      line:     opts.line   || 240,
      lineRule: LineRuleType.AUTO,
    },
    indent: opts.indent || undefined,
    children: runs,
  });
}

function run(text, opts = {}) {
  return new TextRun({
    text:      text || '',
    bold:      opts.bold      || false,
    underline: opts.underline ? { type: UnderlineType.SINGLE } : undefined,
    size:      opts.size      || 20,
    font:      opts.font      || 'Times New Roman',
    italics:   opts.italic    || false,
  });
}

function labelValueRow(label, value) {
  return new TableRow({
    children: [
      cell(para(label, { bold: true, size: 20 }), { width: { size: 3800, type: WidthType.DXA } }),
      cell(para(': ' + (value || ''), { size: 20 }), { width: { size: 5360, type: WidthType.DXA } }),
    ]
  });
}

function ptaRow(label, re, le) {
  return new TableRow({
    children: [
      cell(para(label, { bold: true, size: 20 }), { width: { size: 3800, type: WidthType.DXA } }),
      cell([
        para([run(': RE  : ' + (re || ''), { size: 20 })], {}),
        para([run('  LE  : ' + (le || ''), { size: 20 })], { before: 30 }),
      ], { width: { size: 5360, type: WidthType.DXA } }),
    ]
  });
}

// ══════════════════════════════════════════════════════════════
//  REFERRAL FORM
// ══════════════════════════════════════════════════════════════
function buildReferralForm() {

  const logoH  = 1008000; // ~1.1 inch
  const g20W   = Math.round(logoH * 131 / 149);
  const railW  = Math.round(logoH * 132 / 133);

  const g20Logo = new ImageRun({
    data: g20Buffer,
    transformation: { width: Math.round(g20W / 9144), height: Math.round(logoH / 9144) },
    type: 'png',
  });
  const railLogo = new ImageRun({
    data: railwaysBuffer,
    transformation: { width: Math.round(railW / 9144), height: Math.round(logoH / 9144) },
    type: 'png',
  });

  // Header table: [G20 logo] | [Hospital name] | [Railways logo]
  const headerTable = new Table({
    width: { size: 9160, type: WidthType.DXA },
    columnWidths: [1400, 6360, 1400],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      new TableRow({
        children: [
          cell(
            new Paragraph({ alignment: AlignmentType.CENTER, children: [g20Logo] }),
            { width: { size: 1400, type: WidthType.DXA }, valign: VerticalAlign.CENTER }
          ),
          cell([
            para('I.C.F HOSPITAL, CHENNAI – 600 038', {
              bold: true, align: AlignmentType.CENTER, size: 28, underline: true,
            }),
          ], { width: { size: 6360, type: WidthType.DXA }, valign: VerticalAlign.CENTER }),
          cell(
            new Paragraph({ alignment: AlignmentType.CENTER, children: [railLogo] }),
            { width: { size: 1400, type: WidthType.DXA }, valign: VerticalAlign.CENTER }
          ),
        ]
      })
    ]
  });

  // Office meta table
  const officeTable = new Table({
    width: { size: 9160, type: WidthType.DXA },
    columnWidths: [5080, 4080],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      new TableRow({
        children: [
          cell(para('आ०ओ: ८.ओ.सुब./   प्रधान मुख्य  चिकित्सा अधिकारी का कार्यालय', { size: 18 }),
               { width: { size: 5080, type: WidthType.DXA } }),
          cell(para('Office of the PCMO', { align: AlignmentType.RIGHT, size: 18 }),
               { width: { size: 4080, type: WidthType.DXA } }),
        ]
      }),
      new TableRow({
        children: [
          cell(para('No.MD/ICF/34/H', { size: 18 }), { width: { size: 5080, type: WidthType.DXA } }),
          cell(para('दिनांक/   DATE: ' + data.date, { align: AlignmentType.RIGHT, size: 18 }),
               { width: { size: 4080, type: WidthType.DXA } }),
        ]
      }),
    ]
  });

  // Body fields table
  const bodyTable = new Table({
    width: { size: 9160, type: WidthType.DXA },
    columnWidths: [3800, 5360],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      labelValueRow('NAME OF THE PATIENT',
        'Shri/Smt. ' + data.patient_name + ' / ' + data.patient_age + ' yrs / ' + data.patient_gender),
      labelValueRow('OUT PATIENT REGN.NO',      data.op_number),
      labelValueRow('RELATIONSHIP WITH EMPLOYEE', data.relationship),
      labelValueRow('EMP. NUMBER/RELHS.NO',      data.emp_number + '  ' + data.relhs_number),
      labelValueRow('BASIC PAY',                  data.basic_pay),
      labelValueRow('PROVISIONAL DIAGNOSIS',      data.provisional_diagnosis),
      ptaRow('    PTA REPORTS:',                  data.pta_re, data.pta_le),
      labelValueRow('DATE OF LAST PROCUREMENT',   data.date_of_last_procurement),
      labelValueRow('Recommendation',             data.recommendation),
    ]
  });

  return [
    headerTable,
    para('', { after: 40 }),
    officeTable,
    para('', { after: 60 }),

    para('श्रवण सहायता के लिए पुनर्मूल्यांकन प्रपत्र',
         { align: AlignmentType.CENTER, size: 20 }),
    para('REFERRAL FORM FOR HEARING AID',
         { align: AlignmentType.CENTER, bold: true, size: 22, underline: true }),
    para('', { after: 20 }),
    para('विषय: श्रवण यंत्र खरीदने के लिए रोगी का रेफरल',
         { align: AlignmentType.CENTER, size: 18 }),
    para('Sub: Referral of Patient for procuring Hearing Aid',
         { align: AlignmentType.CENTER, size: 20 }),
    para('✦ ✦ ✦ ✦', { align: AlignmentType.CENTER, size: 18, before: 60, after: 80 }),

    bodyTable,
    para('', { after: 80 }),

    para('The above Patient is referred for procuring of Hearing Aid.', { size: 20 }),
    para('', { after: 60 }),

    new Paragraph({
      alignment: AlignmentType.LEFT,
      spacing: { before: 60, after: 60, line: 276 },
      children: [
        run(
          'The charges for the above Machine/Instruments may be collected from the ' +
          'patient, and bill may be handed over to him/her.',
          { underline: true, bold: true, size: 20 }
        ),
      ]
    }),

    para('', { after: 60 }),
    para('Thanking You', { size: 20 }),
    para('', { after: 200 }),

    para('Signature of recommending officer,', { align: AlignmentType.RIGHT, size: 20 }),
    para('Sr.DMO/ENT, RH/ICF.',               { align: AlignmentType.RIGHT, size: 20 }),
    para('', { after: 60 }),

    para('Mob: 9003058209',            { size: 18 }),
    para('No.273/C,Brocks Road,',      { size: 18 }),
    para('Near Railway Hospital,',     { size: 18 }),
    para('Ayyanavarma, Chennai-23.',   { size: 18 }),
  ];
}

// ══════════════════════════════════════════════════════════════
//  DECLARATION FORM
// ══════════════════════════════════════════════════════════════
function buildDeclarationForm() {
  return [
    para('', { after: 200 }),
    para('DECLARATION FOR HEARING AID',
         { align: AlignmentType.CENTER, bold: true, size: 24, underline: true }),
    para('', { after: 200 }),
    para('Date: ' + data.date, { align: AlignmentType.RIGHT, size: 20 }),
    para('', { after: 200 }),

    new Paragraph({
      alignment: AlignmentType.LEFT,
      spacing: { before: 100, after: 100, line: 360, lineRule: LineRuleType.AUTO },
      indent: { firstLine: 720 },
      children: [
        run(
          'I  \u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026\u2026  ' +
          'solemnly affirm that I have not procured any hearing aid through ' +
          'Integral Coach Factory Hospital, Chennai-38 in the Proceeding five years.',
          { size: 20 }
        ),
      ]
    }),

    para('', { after: 600 }),
    para('Signature of the Employee / Patient',
         { align: AlignmentType.CENTER, size: 20 }),
  ];
}

// ══════════════════════════════════════════════════════════════
//  BUILD & WRITE
// ══════════════════════════════════════════════════════════════
const doc = new Document({
  sections: [
    {
      properties: {
        page: {
          size:   { width: 11906, height: 16838 }, // A4
          margin: { top: 720, right: 1080, bottom: 720, left: 1080 },
        }
      },
      children: buildReferralForm(),
    },
    {
      properties: {
        page: {
          size:   { width: 11906, height: 16838 },
          margin: { top: 720, right: 1080, bottom: 720, left: 1080 },
        }
      },
      children: buildDeclarationForm(),
    }
  ]
});

Packer.toBuffer(doc).then(buf => {
  const outPath = path.join(__dirname, 'ICF_Hearing_Aid_Forms.docx');
  fs.writeFileSync(outPath, buf);
  console.log('OK:' + outPath);
}).catch(e => { console.error(e); process.exit(1); });