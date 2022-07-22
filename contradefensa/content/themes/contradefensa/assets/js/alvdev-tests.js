// Init Appwrite Web SDK
const client = new Appwrite.Client();

client
  .setEndpoint('https://essfera.com/v1') // API Endpoint
  .setProject('62bc4bb22d426cc4437e'); // Project ID

// Appwrite Databases
const databases = new Appwrite.Databases(client, '62db19ea33336bea4376');
const questionsDB = databases.listDocuments('62db19fcc2567543eded');

async function fetchQuestions() {
  const questions = await questionsDB;
  availableQuestions = [...questions['documents']];
  console.log(availableQuestions);
  console.log('#########');
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
const MAX_QUESTIONS = 5;

const questionFormTemplate = `
  <h2 id="question">
      What is the answer to this question?
  </h2>
  <div class="choice-container">
      <p class="choice-prefix">a</p>
      <p class="choice-text" data-number="1">Choice 1</p>
  </div>
  <div class="choice-container">
      <p class="choice-prefix">b</p>
      <p class="choice-text" data-number="2">Choice 2</p>
  </div>
  <div class="choice-container">
      <p class="choice-prefix">c</p>
      <p class="choice-text" data-number="3">Choice 3</p>
  </div>
  <div class="choice-container">
      <p class="choice-prefix">d</p>
      <p class="choice-text" data-number="4">Choice 4</p>
  </div>
`;

document.addEventListener('DOMContentLoaded', e => {
  console.log('content loaded');
  questionForm.innerHTML = questionFormTemplate;
  const question = document.querySelector('#question');
  const answers = Array.from(document.querySelectorAll('.choice-text'));

  getNewQuestion = () => {
    if (availableQuestions.length === 0 || questionCounter >= MAX_QUESTIONS) {
      // TODO: go to final page after quiz ends
      return window.location.assign('/');
    }

    questionCounter++;
    const questionIndex = Math.floor(Math.random() * availableQuestions.length);
    currentQuestion = availableQuestions[questionIndex];
    console.log('>>>>>>>>> ');
    console.log(currentQuestion);
    question.innerText = currentQuestion.question;

    answers.forEach(choice => {
      const number = choice.dataset['number'];
      choice.innerText = currentQuestion['answers'][number];
    });

    availableQuestions.splice(questionIndex, 1);

    acceptingAnswers = true;
  };

  answers.forEach(choice => {
    choice.addEventListener('click', e => {
      if (!acceptingAnswers) return;

      acceptingAnswers = false;
      const selectedChoice = e.target;
      const selectedAnswer = selectedChoice.dataset['number'];
      getNewQuestion();
    });
  });
});

startGame = () => {
  questionCounter = 0;
  score = 0;
  availableQuestions = fetchQuestions();
};

startGame();
