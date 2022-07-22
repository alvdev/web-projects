// Appwrite
const client = new Appwrite.Client();

// Init your Web SDK
client
  .setEndpoint('https://essfera.com/v1') // Your API Endpoint
  .setProject('62bc4bb22d426cc4437e'); // Your project ID

const account = new Appwrite.Account(client);

// Register User
account.create('unique()', 'me@example.com', 'password', 'John Doe').then(
  response => {
    console.log(response);
  },
  error => {
    console.log(error);
  }
);

// Directus
async function fetchQuestions() {
  const response = await fetch('http://contradefensa.com:8055/items/questions');
  const data = await response.json();
  const questions = data['data'];
  availableQuestions = [...questions];
}

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
  const choices = Array.from(document.querySelectorAll('.choice-text'));

  getNewQuestion = () => {
    if (availableQuestions.length === 0 || questionCounter >= MAX_QUESTIONS) {
      // TODO: go to final page after quiz ends
      return window.location.assign('/');
    }

    questionCounter++;
    const questionIndex = Math.floor(Math.random() * availableQuestions.length);
    currentQuestion = availableQuestions[questionIndex];
    question.innerText = currentQuestion.question;

    choices.forEach(choice => {
      const number = choice.dataset['number'];
      choice.innerText = currentQuestion['choice' + number];
    });

    availableQuestions.splice(questionIndex, 1);

    acceptingAnswers = true;
  };

  choices.forEach(choice => {
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
