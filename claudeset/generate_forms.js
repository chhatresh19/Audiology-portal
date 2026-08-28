const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, BorderStyle, WidthType, VerticalAlign, UnderlineType,
  PageNumber, Header, ShadingType, TabStopType, TabStopPosition,
  LineRuleType
} = require('docx');
const fs = require('fs');

// ── Accept patient data from CLI or use defaults ──────────────────
const data = JSON.parse(process.argv[2] || JSON.stringify({
  patient_name: 'Smt. RUTHAMMA w/o Shri. YESUDASS',
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
}));

const noBorder = { style: BorderStyle.NONE, size: 0, color: 'FFFFFF' };
const noBorders = { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder };

const thinBorder = { style: BorderStyle.SINGLE, size: 1, color: '000000' };
const thinBorders = { top: thinBorder, bottom: thinBorder, left: thinBorder, right: thinBorder };

function cell(children, opts = {}) {
  return new TableCell({
    borders: opts.borders || noBorders,
    width: opts.width || undefined,
    verticalAlign: opts.valign || VerticalAlign.TOP,
    columnSpan: opts.colspan || 1,
    shading: opts.shading || undefined,
    margins: { top: 40, bottom: 40, left: 60, right: 60 },
    children: Array.isArray(children) ? children : [children],
  });
}

function para(text, opts = {}) {
  return new Paragraph({
    alignment: opts.align || AlignmentType.LEFT,
    spacing: { before: opts.before || 0, after: opts.after || 0, line: opts.line || 240, lineRule: LineRuleType.AUTO },
    children: Array.isArray(text) ? text : [
      new TextRun({
        text: text || '',
        bold: opts.bold || false,
        underline: opts.underline ? { type: UnderlineType.SINGLE } : undefined,
        size: opts.size || 20,
        font: opts.font || 'Times New Roman',
      })
    ],
  });
}

function run(text, opts = {}) {
  return new TextRun({
    text: text || '',
    bold: opts.bold || false,
    underline: opts.underline ? { type: UnderlineType.SINGLE } : undefined,
    size: opts.size || 20,
    font: opts.font || 'Times New Roman',
    italics: opts.italic || false,
  });
}

function labelValueRow(label, value) {
  return new TableRow({
    children: [
      cell(para(label, { bold: true, size: 20 }), { width: { size: 3800, type: WidthType.DXA } }),
      cell(para(': ' + (value || ''), { size: 20 }), { width: { size: 5560, type: WidthType.DXA } }),
    ]
  });
}

function ptaRow(label, re, le) {
  return new TableRow({
    children: [
      cell(para(label, { bold: true, size: 20 }), { width: { size: 3800, type: WidthType.DXA } }),
      cell([
        para([run(': ', { size: 20 }), run('RE', { bold: true, size: 20 }), run('  : ' + (re || ''), { size: 20 })], {}),
        para([run('  ', { size: 20 }), run('LE', { bold: true, size: 20 }), run('  : ' + (le || ''), { size: 20 })], { before: 40 }),
      ], { width: { size: 5560, type: WidthType.DXA } }),
    ]
  });
}

// ══════════════════════════════════════════════════════════════════
//  REFERRAL FORM
// ══════════════════════════════════════════════════════════════════
function buildReferralForm() {

  const headerTable = new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [2000, 5360, 2000],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      new TableRow({
        children: [
          // Left: G20 logo placeholder
          cell(para('🔷', { align: AlignmentType.CENTER, size: 36 }), { width: { size: 2000, type: WidthType.DXA } }),
          // Centre: Hospital name
          cell([
            para('I.C.F HOSPITAL, CHENNAI – 600 038', { bold: true, align: AlignmentType.CENTER, size: 28, underline: true }),
          ], { width: { size: 5360, type: WidthType.DXA }, valign: VerticalAlign.CENTER }),
          // Right: Railway logo placeholder
          cell(para('🚂', { align: AlignmentType.CENTER, size: 36 }), { width: { size: 2000, type: WidthType.DXA } }),
        ]
      })
    ]
  });

  const officeTable = new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [4680, 4680],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      new TableRow({
        children: [
          cell([
            para('आ०ओ: ८.ओ.सुब./                      प्रधान मुख्य  चिकित्सा अधिकारी का', { size: 18 }),
            para('कार्यालय', { size: 18 }),
          ], { width: { size: 4680, type: WidthType.DXA } }),
          cell([
            para('Office of the PCMO', { align: AlignmentType.RIGHT, size: 18 }),
          ], { width: { size: 4680, type: WidthType.DXA } }),
        ]
      }),
      new TableRow({
        children: [
          cell(para('No.MD/ICF/34/H', { size: 18 }), { width: { size: 4680, type: WidthType.DXA } }),
          cell(para('दिनांक/   DATE: ' + data.date, { align: AlignmentType.RIGHT, size: 18 }), { width: { size: 4680, type: WidthType.DXA } }),
        ]
      }),
    ]
  });

  const bodyTable = new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [3800, 5560],
    borders: { top: noBorder, bottom: noBorder, left: noBorder, right: noBorder, insideH: noBorder, insideV: noBorder },
    rows: [
      labelValueRow('NAME OF THE PATIENT', `Shri/Smt. ${data.patient_name}/${data.patient_age} yrs/${data.patient_gender}`),
      labelValueRow('OUT PATIENT REGN.NO', data.op_number),
      labelValueRow('RELATIONSHIP WITH EMPLOYEE', data.relationship),
      labelValueRow('EMP. NUMBER/RELHS.NO', `${data.emp_number} ${data.relhs_number}`),
      labelValueRow('BASIC PAY', data.basic_pay),
      labelValueRow('PROVISIONAL DIAGNOSIS', data.provisional_diagnosis),
      ptaRow('    PTA REPORTS:', data.pta_re, data.pta_le),
      labelValueRow('DATE OF LAST PROCUREMENT', data.date_of_last_procurement),
      labelValueRow('Recommendation', data.recommendation),
    ]
  });

  return [
    // ── Header ───────────────────────────────────────
    headerTable,
    para(''),
    officeTable,
    para(''),
    // ── Title ────────────────────────────────────────
    para('श्रवण सहायता के लिए पुनर्मूल्यांकन प्रपत्र', { align: AlignmentType.CENTER, size: 20 }),
    para('REFERRAL FORM FOR HEARING AID', { align: AlignmentType.CENTER, bold: true, size: 22, underline: true }),
    para(''),
    para('विषय: श्रवण यंत्र खरीदने के लिए रोगी का रेफरल', { align: AlignmentType.CENTER, size: 18 }),
    para('Sub: Referral of Patient for procuring Hearing Aid', { align: AlignmentType.CENTER, size: 20 }),
    para('✦✦✦✦', { align: AlignmentType.CENTER, size: 20, before: 60, after: 60 }),
    // ── Body fields ──────────────────────────────────
    bodyTable,
    para(''),
    para('The above Patient is referred for procuring of Hearing Aid.', { size: 20, before: 80 }),
    para(''),
    new Paragraph({
      alignment: AlignmentType.LEFT,
      spacing: { before: 60, after: 60, line: 276 },
      children: [
        run('The charges for the above Machine/Instruments may be collected from the patient, and bill may be handed over to him/her.', { underline: true, bold: true, size: 20 }),
      ]
    }),
    para(''),
    para('Thanking You', { size: 20, before: 80 }),
    para(''),
    para(''),
    para('Signature of recommending officer,', { align: AlignmentType.RIGHT, size: 20 }),
    para('Sr.DMO/ENT, RH/ICF.', { align: AlignmentType.RIGHT, size: 20 }),
    para(''),
    para('Mob: 9003058209', { size: 18 }),
    para(''),
    para('No.273/C,Brocks Road,', { size: 18 }),
    para('Near Railway Hospital,', { size: 18 }),
    para('Ayyanavarma, Chennai-23.', { size: 18 }),
  ];
}

// ══════════════════════════════════════════════════════════════════
//  DECLARATION FORM
// ══════════════════════════════════════════════════════════════════
function buildDeclarationForm() {
  return [
    para(''),
    para(''),
    para(''),
    para('DECLARATION FOR HEARING AID', { align: AlignmentType.CENTER, bold: true, size: 24, underline: true }),
    para(''),
    para(''),
    para('Date: ' + data.date, { align: AlignmentType.RIGHT, size: 20 }),
    para(''),
    para(''),
    new Paragraph({
      alignment: AlignmentType.LEFT,
      spacing: { before: 100, after: 100, line: 360, lineRule: LineRuleType.AUTO },
      indent: { firstLine: 720 },
      children: [
        run('I  ………………………………………………  solemnly affirm that I have not procured any hearing aid through Integral Coach Factory Hospital, Chennai-38 in the Proceeding five years.', { size: 20 }),
      ]
    }),
    para(''),
    para(''),
    para(''),
    para(''),
    para(''),
    para('Signature of the Employee / Patient', { align: AlignmentType.CENTER, size: 20 }),
  ];
}

// ══════════════════════════════════════════════════════════════════
//  ASSEMBLE DOCUMENT (2 pages)
// ══════════════════════════════════════════════════════════════════
const doc = new Document({
  sections: [
    {
      properties: {
        page: {
          size: { width: 11906, height: 16838 }, // A4
          margin: { top: 720, right: 1080, bottom: 720, left: 1080 },
        }
      },
      children: buildReferralForm(),
    },
    {
      properties: {
        page: {
          size: { width: 11906, height: 16838 },
          margin: { top: 720, right: 1080, bottom: 720, left: 1080 },
        }
      },
      children: buildDeclarationForm(),
    }
  ]
});

Packer.toBuffer(doc).then(buf => {
  const outPath = process.argv[3] || '/mnt/user-data/outputs/ICF_Hearing_Aid_Forms.docx';
  fs.mkdirSync(require('path').dirname(outPath), { recursive: true });
  fs.writeFileSync(outPath, buf);
  console.log('OK:' + outPath);
}).catch(e => { console.error(e); process.exit(1); });
