// Init Appwrite Web SDK
const client = new Appwrite.Client();

client
  .setEndpoint('http://localhost:8010/v1') // API Endpoint
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
  <div class="answer-container">
      <p class="answer-prefix">a</p>
      <p class="answer-text" data-number="1">answer 1</p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">b</p>
      <p class="answer-text" data-number="2">answer 2</p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">c</p>
      <p class="answer-text" data-number="3">answer 3</p>
  </div>
  <div class="answer-container">
      <p class="answer-prefix">d</p>
      <p class="answer-text" data-number="4">answer 4</p>
  </div>
`;

document.addEventListener('DOMContentLoaded', e => {
  console.log('content loaded');
  questionForm.innerHTML = questionFormTemplate;
  const question = document.querySelector('#question');
  const answers = Array.from(document.querySelectorAll('.answer-text'));

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
    const questionHtml = `
      <h2 id="question">
          What is the answer to this question?
      </h2>    
    `;

    console.log(answers);

    answers.forEach((answer, index) => {
      // TODO: Show answers randomly
      // let randIndex = Math.floor(Math.random() * answers.length);
      answer.innerText = currentQuestion['answers'][index];
      // const answerHtml = `
      //   <div class="answer-container">
      //       <p class="answer-prefix">b</p>
      //       <p class="answer-text" data-number="2">${currentQuestion['answers'][index]}</p>
      //   </div>
      // `;
      // questionForm.insertAdjacentHTML('beforeend', answerHtml);
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
      const selectedanswer = e.target;
      const selectedAnswer = selectedanswer.dataset['number'];
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
