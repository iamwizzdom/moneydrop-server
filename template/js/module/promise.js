const isPrime = num => {
    for(let i = 2, s = Math.sqrt(num); i <= s; i++)
        if(num % i === 0) return false;
    return num > 1;
};

const promise = (num, isPrime) => new Promise((resolve, reject) => {
    let time = new Date();
    let startTime = time.getTime();
    isPrime = isPrime(num);
    let endTime = time.getTime();
    resolve({status: isPrime, time: endTime - startTime});
});

const numbers = [3, 22, 6];
let promises = [];
for (let i = 0; i < numbers.length; i++) {
    promises.push(promise(numbers[i], isPrime));
}

let responseTimes = [];

promises.map(promise => {
    promise.then(result => {
        responseTimes.push(result.time);
    });
});

let lowestTime = Math.min.apply(null, responseTimes);