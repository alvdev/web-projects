export const parsePublishDate = str => {
  // str: "02-10-2021 19:09"
  const [date, time] = str.split(" ");
  const [day, month, year] = date.split("-");
  return new Date(`${year}-${month}-${day}T${time}`);
};

export const removeTime = str => {
  // str: "02-10-2021 19:09"
  const [date] = str.split(" ");
  return date;
};
