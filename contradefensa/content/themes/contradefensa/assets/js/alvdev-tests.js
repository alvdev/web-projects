const question = document.querySelector('#question');
const choices = Array.from(document.querySelectorAll('.choice-text'));

let currentQuestion = {};
let acceptingAnswers = true;
let score = 0;
let questionCounter = 0;
let availableQuestions = [];

let questions = [
  {
    question: 'This is the first question',
    choice1: 'Choice 1',
    choice2: 'Choice 2',
    choice3: 'Choice 3',
    choice4: 'Choice 4',
    answer: 3,
  },
  {
    question: 'This is the second question',
    choice1: 'Choice 1',
    choice2: 'Choice 2',
    choice3: 'Choice 3',
    choice4: 'Choice 4',
    answer: 4,
  },
  {
    question: 'This is the third question',
    choice1: 'Choice 1',
    choice2: 'Choice 2',
    choice3: 'Choice 3',
    choice4: 'Choice 4',
    answer: 2,
  },
  {
    question: 'This is the fourth question',
    choice1: 'Choice 1',
    choice2: 'Choice 2',
    choice3: 'Choice 3',
    choice4: 'Choice 4',
    answer: 1,
  },
];

// Constants
const CORRECT_BONUS = 10;
const MAX_QUESTIONS = 3;
