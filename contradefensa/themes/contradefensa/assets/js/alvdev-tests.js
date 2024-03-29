// Init Appwrite Web SDK
const client = new Appwrite.Client();

client
  .setEndpoint('https://app.essfera.com/v1') // API Endpoint
  .setProject('62bc4bb22d426cc4437e'); // Project ID

// Appwrite Databases
const databases = new Appwrite.Databases(client, '62db19ea33336bea4376');
const questionsDB = databases.listDocuments('62db19fcc2567543eded');

async function fetchQuestions() {
  const questions = await questionsDB;
  availableQuestions = [...questions['documents']];
  console.log('#########');
  console.log(availableQuestions);
  console.log('#########');
  getNewQuestion();
}

// Appwrite Register User Example
// const account = new Appwrite.Account(client);

// account.create('unique()', 'me@example.com', 'password', 'John Doe').then(
//   response => {
//     console.log(response);
//   },
//   error => {
//     console.log(error);
//   }
// );

// Directus
// async function fetchQuestions() {
//   const response = await fetch('http://contradefensa.com:8055/items/questions');
//   const data = await response.json();
//   const questions = data['data'];
//   availableQuestions = [...questions];
// }

let currentQuestion = {};
let acceptingAnswers = true;
let questionCounter = 0;
let score = 0;
let availableQuestions = [];

// Constants
const CORRECT_BONUS = 10;
const TOTAL_QUESTIONS = 5;

const questionFormTemplate = `
  <h2 id="question"></h2>
  <div class="answer-container">
      <p class="answer-prefix">a</p>
      <p class="answer-text" data-number="1"></p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">b</p>
      <p class="answer-text" data-number="2"></p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">c</p>
      <p class="answer-text" data-number="3"></p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">d</p>
      <p class="answer-text" data-number="4"></p>
  </div>
`;

// TODO: Create form dinamically
// let questionFormHtml;

console.log('content loaded');
questionForm.innerHTML = questionFormTemplate;
const question = document.querySelector('#question');
const answers = Array.from(document.querySelectorAll('.answer-text'));

getNewQuestion = () => {
  if (availableQuestions.length === 0 || questionCounter >= TOTAL_QUESTIONS) {
    // TODO: go to final page after quiz ends
    return window.location.assign('/tests');
  }

  document.querySelector('.current-question').innerText = questionCounter + 1;
  document.querySelector('.total-questions').innerText = TOTAL_QUESTIONS;

  questionCounter++;
  const questionIndex = Math.floor(Math.random() * availableQuestions.length);
  currentQuestion = availableQuestions[questionIndex];
  console.log('>>>>>>>>> ');
  console.log(currentQuestion.correct_answer);
  question.innerText = currentQuestion.question;

  // TODO: Create form dinamically
  // questionFormHtml = `
  //     <h2 id="question">
  //         ${currentQuestion.question}
  //     </h2>
  //   `;

  console.log(answers);

  answers.forEach((answer, index) => {
    // TODO: Show answers randomly
    // let randIndex = Math.floor(Math.random() * answers.length);
    answer.innerText = currentQuestion['answers'][index];

    // TODO: Create form dinamically
    // questionFormHtml += `
    //     <div class="answer-container">
    //         <p class="answer-prefix">b</p>
    //         <p class="answer-text" data-number="2">${currentQuestion['answers'][index]}</p>
    //     </div>
    //   `;
  });
  // Remove answered question
  availableQuestions.splice(questionIndex, 1);

  acceptingAnswers = true;

  // Remove HTML undefined answers containers
  // const undefinedAnswer = document.querySelectorAll('.answerContainer');
  // answers.forEach(answer => {
  //   if (answer.innerHTML == undefined) return;
  // });
};

answers.forEach(answer => {
  answer.addEventListener('click', e => {
    if (!acceptingAnswers) return;

    acceptingAnswers = false;
    const selectedChoice = e.target;
    const selectedAnswer = selectedChoice.textContent;

    // Check if picked choice is the correct answer
    console.log(selectedAnswer === currentQuestion.correct_answer);

    const classToApply =
      selectedAnswer === currentQuestion.correct_answer
        ? 'correct'
        : 'incorrect';

    selectedChoice.classList.add(classToApply);

    if (selectedAnswer === currentQuestion.correct_answer) {
      score += 1;
      document.querySelector('.hub-score span').innerText = score;
    }

    setTimeout(() => {
      selectedChoice.classList.remove(classToApply);
      getNewQuestion();
    }, 1000);
  });
});

startGame = () => {
  questionCounter = 0;
  score = 0;
  availableQuestions = fetchQuestions();
};

startGame();
