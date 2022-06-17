const question = document.querySelector('#question');
const choices = Array.from(document.querySelectorAll('.choice-text'));

let currentQuestion = {};
let acceptingAnswers = true;
let questionCounter = 0;
let score = 0;
let availableQuestions = [];

let questions = [
  {
    question: 'This is the first question',
    choice1: 'Choice 1 to first question',
    choice2: 'Choice 2 to first question',
    choice3: 'Choice 3 to first question',
    choice4: 'Choice 4 to first question',
    answer: 3,
  },
  {
    question: 'This is the second question',
    choice1: 'Choice 1 to second question',
    choice2: 'Choice 2 to second question',
    choice3: 'Choice 3 to second question',
    choice4: 'Choice 4 to second question',
    answer: 4,
  },
  {
    question: 'This is the third question',
    choice1: 'Choice 1 to third question',
    choice2: 'Choice 2 to third question',
    choice3: 'Choice 3 to third question',
    choice4: 'Choice 4 to third question',
    answer: 2,
  },
  {
    question: 'This is the fourth question',
    choice1: 'Choice 1 to fourth question',
    choice2: 'Choice 2 to fourth question',
    choice3: 'Choice 3 to fourth question',
    choice4: 'Choice 4 to fourth question',
    answer: 1,
  },
];

// Constants
const CORRECT_BONUS = 10;
const MAX_QUESTIONS = 3;

startGame = () => {
  questionCounter = 0;
  score = 0;
  availableQuestions = [...questions];
  console.log(availableQuestions);
  getNewQuestion();
};

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

startGame();
