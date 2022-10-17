var cardsH = document.querySelector('.ccards');
var cardsHeight = cardsH.clientHeight;
var maleSex = 'Hombre';
var femaleSex = 'Mujer';
var firstMArr = [
  'Agapito',
  'Anacleto',
  'Arnulfo',
  'Arsenio',
  'Barry',
  'Benemérito',
  'Bonifacio',
  'Bruto',
  'Cojoncio',
  'Crescencio',
  'Darwin',
  'Eladio',
  'Eleuterio',
  'Elvis',
  'Epifanio',
  'Espaminondo',
  'Eustaquio',
  'Filemón',
  'Florencio',
  'Fulgencio',
  'Gervasio',
  'Herculano',
  'Hilario',
  'Horacio',
  'Igor',
  'Inolfo',
  'Ladislao',
  'Pancracio',
  'Pascacio',
  'Próculo',
  'Protasio',
  'Ruperto',
  'Segismundo',
  'Tesifonte',
  'Tiburcio',
  'Torcuato',
  'Uldarico',
  'Vitorio',
  'Vitorino',
];

var firstFArr = [
  'Agapita',
  'Apolonia',
  'Bernadette',
  'Briseida',
  'Camelia',
  'Cananea',
  'Felipa',
  'Gregoria',
  'Gertrudis',
  'Helga',
  'Hermenegilda',
  'Irma',
  'Isidra',
  'Macaria',
  'Mildred',
  'Pancracia',
  'Pascasia',
  'Petronila',
  'Sibilia',
  'Tiburcia',
  'Tránsito',
  'Úrsula',
  'Uxia',
  'Abigail',
  'Agnes',
  'Alberta',
  'Bárbara',
  'Beverly',
  'Camille',
  'Charlotte',
  'Deirdre',
  'Desdemona',
  'Freda',
  'Gertrudis',
  'Gretchen',
  'Helga',
  'Henrietta',
  'Jezabel',
  'Kimberly',
  'Latasha',
  'Leah',
  'Lilith',
  'Mirta',
];

var lastArr = [
  'Joroba',
  'Pieldelobo',
  'Rajado',
  'Zuzunaga',
  'Bonachera',
  'Oreja de Perro',
  'Pechoabierto',
  'Del Cacho',
  'Venenosa',
  'Piesplanos',
  'Seisdedos',
  'Alcoholado',
  'Feucha',
  'Guapo',
  'Perroverde',
  'Piernavieja',
  'Zasca',
  'Chinchurreta',
  'Gandula',
  'Piernabierta',
  'De la Polla',
  'Parahoy',
  'Adistancia',
  'Tetorras',
  'Pichilengue',
  'Verdugo',
  'Melapela',
  'Cabezabuque',
  'Calvete',
  'Prieto',
  'Garrido',
  'Urriaga',
  'Melgar',
  'Sorda',
  'Zuzunaga',
  'Aguanta',
  'Amozurrutia',
  'Cabrera',
  'Cancelada',
];
var mName, fName;
var char = '0123456789';
var cc = document.querySelector('.ccard');
var ccType = document.querySelector('[data-cc=type]');
var ccNum = document.querySelector('[data-cc=num]');
var ccDate = document.querySelector('[data-cc=date]');
var ccSecCode = document.querySelector('[data-cc=sec-code]');

// generate a random number
function randomNumber(n) {
  return Math.floor(Math.random() * n);
}

// generate a random first and last name (male)
function genMName() {
  var rNum1 = randomNumber(firstMArr.length);
  var rNum2 = randomNumber(lastArr.length);
  mName = firstMArr[rNum1] + ' ' + lastArr[rNum2];
  document.querySelector('[data-cc=person]').textContent = mName;
}
// generate a random first and last name (female)
function genFName() {
  var rNum1 = randomNumber(firstFArr.length);
  var rNum2 = randomNumber(lastArr.length);
  fName = firstFArr[rNum1] + ' ' + lastArr[rNum2];
  document.querySelector('[data-cc=person]').textContent = fName;
}

// render new name onclick
var detectSex = document.querySelector('[data-gender]');
detectSex.onclick = function () {
  if (this.getAttribute('data-gender') === 'male') {
    this.setAttribute('data-gender', 'female');
    this.innerHTML = maleSex;

    // reset gender icon hight to match cards
    cardsHeight = cardsH.clientHeight;
    document.querySelector('[data-gender]').style.height = cardsHeight + 'px';

    // generate a female name
    genFName();
  } else {
    this.setAttribute('data-gender', 'male');
    this.innerHTML = femaleSex;

    // reset gender icon hight to match cards
    cardsHeight = cardsH.clientHeight;
    document.querySelector('[data-gender]').style.height = cardsHeight + 'px';

    // generate a male name
    genMName();
  }
};
// trigger name display onload
detectSex.click();

// generate random expiration date
function genMonth() {
  var date = new Date();
  var arr = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
  var rNum = randomNumber(arr.length);
  var year = date.getFullYear().toString();
  year = year.substr(2, year.length);
  year = parseInt(parseInt(year) + 2);
  ccDate.textContent = parseInt(rNum + 1) + '/' + year;
}

function VisaCard() {
  ccType.textContent = 'VISA';
  genMonth();

  var genNum = '',
    genSC = '',
    i;

  // generate card number
  for (i = 0; i < 15; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genNum += char.substring(rnum, rnum + 1);
  }

  genNum = '4' + genNum;
  ccNum.textContent = genNum.replace(/\d{4}(?=.)/g, '$& ');

  // generate security code
  for (i = 0; i < 3; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genSC += char.substring(rnum, rnum + 1);
  }
  ccSecCode.textContent = genSC;
}
function MasterCard() {
  ccType.textContent = 'MasterCard';
  genMonth();

  var genNum = '',
    genSC = '',
    i;

  // generate card number
  for (i = 0; i < 15; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genNum += char.substring(rnum, rnum + 1);
  }

  genNum = '5' + genNum;
  ccNum.textContent = genNum.replace(/\d{4}(?=.)/g, '$& ');

  // generate security code
  for (i = 0; i < 3; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genSC += char.substring(rnum, rnum + 1);
  }
  ccSecCode.textContent = genSC;
}

function AMexCard() {
  ccType.textContent = 'American Express';
  genMonth();

  var genNum = '',
    genSC = '',
    i;

  // generate card number
  for (i = 0; i < 14; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genNum += char.substring(rnum, rnum + 1);
  }

  genNum = '3' + genNum;
  ccNum.textContent = genNum.replace(/\d{4}(?=.)/g, '$& ');

  // generate security code
  for (i = 0; i < 4; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genSC += char.substring(rnum, rnum + 1);
  }
  ccSecCode.textContent = genSC;
}

function DiscoverCard() {
  ccType.textContent = 'Discover';
  genMonth();

  var genNum = '',
    genSC = '',
    i;

  // generate card number
  for (i = 0; i < 15; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genNum += char.substring(rnum, rnum + 1);
  }

  genNum = '6' + genNum;
  ccNum.textContent = genNum.replace(/\d{4}(?=.)/g, '$& ');

  // generate security code
  for (i = 0; i < 3; i++) {
    var rnum = Math.floor(Math.random() * char.length);
    genSC += char.substring(rnum, rnum + 1);
  }
  ccSecCode.textContent = genSC;
}

// Generate visa card onload
VisaCard();

// responsiveness
function responsiveness() {
  cardsHeight = cardsH.clientHeight;
  document.querySelector('[data-gender]').style.height = cardsHeight + 'px';
}
window.onresize = function () {
  responsiveness();
};
responsiveness();
