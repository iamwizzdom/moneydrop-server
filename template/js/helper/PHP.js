//Git: https://github.com/iamwizzdom/PHP.js
let php = {
    sprintf: function () {
        let args_array = arguments, format = args_array[0];
        if (format.search("%s") < 0) throw "sprintf expects %s " +
        "as its format argument";
        let i = 0, size = (args_array.length - 1);
        if (size >= 100) throw "sprintf arguments can't be greater than 100";
        for (; i < size; i++) {
            format = format.replace("%s", args_array[(i + 1)]);
        }
        return format;
    },
    ucfirst: function (variable) {
        variable = variable.trim();
        return (variable.substring(0, 1).toUpperCase() +
            variable.substring(1, variable.length));
    },
    lcfirst: function (variable) {
        variable = variable.trim();
        return (variable.substring(0, 1).toLowerCase() +
            variable.substring(1, variable.length));
    },
    ucword: function (variable) {
        variable = variable.trim().split(" ");
        let txt = "";
        while (variable.length > 0) {
            txt += (this.empty(txt) ? "" : " ") +
                this.ucfirst(variable[0]);
            variable.shift();
        }
        return txt;
    },
    explode: function (delimiter, variable) {
        return (variable.split(delimiter));
    },
    implode: function (delimiter, variable) {
        return (variable.join(delimiter));
    },
    var_dump: function (object) {
        let x, txt = "", element = document.createElement("pre");
        for (x in object) {
            if (object.hasOwnProperty(x)) {
                txt += x + ": " + object[x] + "\n";
            }
        }
        let old_element = document.getElementById("var_dump");
        if (old_element !== null) {
            document.body.removeChild(old_element);
        }
        element.setAttribute("id", "var_dump");
        element.innerHTML = txt;
        document.body.appendChild(element);
        document.body.insertBefore(element, document.body.firstChild);
        return txt;
    },
    array_intersect: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_intersect expects " +
        "both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (array2.hasOwnProperty(n)) {
                        if (array1[x] === array2[n]) match.push(array1[x]);
                    }
                }
            }
        }
        return match;
    },
    array_intersect_key: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_intersect_key expects " +
        "both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (x === n) match.push(array1[x]);
                }
            }
        }
        return match;
    },
    array_intersect_assoc: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_intersect_assoc expects " +
        "both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (array2.hasOwnProperty(n)) {
                        if (x === n && array1[x] === array2[n]) match.push(array1[x]);
                    }
                }
            }
        }
        return match;
    },
    array_diff: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_diff expects" +
        " both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (array2.hasOwnProperty(n)) {
                        if (array1[x] !== array2[n]) match.push(array1[x]);
                    }
                }
            }
        }
        return match;
    },
    array_diff_key: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_diff_key expects" +
        " both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (x !== n) match.push(array1[x]);
                }
            }
        }
        return match;
    },
    array_diff_assoc: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_diff_assoc expects" +
        " both parameters to an array";
        let x, match = [];
        for (x in array1) {
            if (array1.hasOwnProperty(x)) {
                let n;
                for (n in array2) {
                    if (array2.hasOwnProperty(n)) {
                        if (x !== n && array1[x] !== array2[n]) match.push(array1[x]);
                    }
                }
            }
        }
        return match;
    },
    array_flip: function (array) {
        if (!this.is_array(array)) throw "array_flip expects an array";
        let x, new_array = [];
        for (x in array) {
            if (array.hasOwnProperty(x)) {
                new_array[array[x]] = x;
            }
        }
        return new_array;
    },
    array_keys: function (array) {
        if (!this.is_array(array)) throw "array_keys expects an array";
        let x, new_array = [];
        for (x in array) {
            if (array.hasOwnProperty(x)) {
                new_array.push(x);
            }
        }
        return new_array;
    },
    array_values: function (array) {
        if (!this.is_array(array)) throw "array_values expects an array";
        let x, new_array = [];
        for (x in array) {
            if (array.hasOwnProperty(x)) {
                new_array.push(array[x]);
            }
        }
        return new_array;
    },
    array_reverse: function (array) {
        if (!this.is_array(array)) throw "array_reverse expects an array";
        return array.reverse();
    },
    array_search: function (array, search) {
        if (!this.is_array(array)) throw "array_search expects" +
        " its first parameter to be an array";
        let x, value = false;
        for (x in array) {
            if (array.hasOwnProperty(x) &&
                array[x] === search) {
                value = x;
                break;
            }
        }
        return value;
    },
    array_sum: function (array) {
        if (!this.is_array(array)) throw "array_sum expects an array";
        let x, value = 0;
        for (x in array) {
            if (array.hasOwnProperty(x) && !isNaN(array[x])) {
                value = (value + parseInt(array[x]));
            }
        }
        return value;
    },
    in_array: function (array, search) {
        if (!this.is_array(array)) throw "in_array expects" +
        " its first parameter to be an array";
        let x, found = false;
        for (x in array) {
            if (array.hasOwnProperty(x) &&
                array[x] === search) {
                found = true;
                break;
            }
        }
        return found;
    },
    array_walk: function (array, callback) {
        if (!this.is_array(array) && !this.is_object(array)) throw "array_walk expects" +
        " its first parameter to be an array or an object, got " + typeof array;
        if (!this.is_callable(callback)) throw "array_walk expects" +
        " its second parameter to be a function, got " + typeof callback;
        let x, count = 0, size = (this.is_array(array) ? array.length :
            (this.is_object(array) ? Object.keys(array).length : 0));
        for (x in array) {
            if (array.hasOwnProperty(x) && ++count <= size)
                callback(x, array[x])
        }
    },
    array_merge: function (array1, array2) {
        if (!this.is_array(array1) || !this.is_array(array2)) throw "array_merge expects" +
        " both parameters to an array";
        return array1.concat(array2);
    },
    array_shuffle: function (array) {
        if (!this.is_array(array)) throw "array_shuffle expects an array";
        let size = array.length;
        for (let i = (size - 1); i >= 0; i--) {
            let j = Math.floor((Math.random() * size));
            let random = array[j];
            array[j] = array[i];
            array[i] = random;
        }
        return array;
    },
    str_shuffle: function (string) {
        return this.array_shuffle(string.toString().trim().split("")).join("");
    },
    word_shuffle: function (string) {
        return this.array_shuffle(string.toString().trim().split(" ")).join(" ");
    },
    is_array: function (variable) {
        return Array.isArray(variable);
    },
    is_numeric: function (variable) {
        return (!isNaN(variable));
    },
    is_boolean: function (variable) {
        return typeof variable === "boolean";
    },
    is_object: function (variable) {
        return typeof variable === "object";
    },
    is_string: function (variable) {
        return typeof variable === "string";
    },
    is_callable: function (variable) {
        return typeof variable === "function" || variable instanceof Function;
    },
    time: function () {
        return parseInt((new Date()).getTime() / 1000);
    },
    strtotime: function (string) {
        return ((new Date(string)).toDateString());
    },
    time_ago: function (time) {
        if (!time) return;
        time = time.toString(); //convert time to string
        time = time.replace(/\.\d+/, ""); // remove milliseconds
        time = time.replace(/-/, "/").replace(/-/, "/");
        time = time.replace(/T/, " ").replace(/Z/, " UTC");
        time = time.replace(/([\+\-]\d\d)\:?(\d\d)/, " $1$2"); // -04:00 -> -0400
        time = new Date(time * 1000 || time);

        let now = new Date(),
            seconds = ((now.getTime() - time) * .001) >> 0,
            minutes = Math.round(seconds / 60),
            hours = Math.round(seconds / 3600),
            days = Math.round(seconds / 86400),
            weeks = Math.round(seconds / 604800),
            months = Math.round(seconds / 2629440),
            years = Math.round(seconds / 31553280);

        let templates = {
            prefix: "",
            suffix: " ago",
            seconds: "less than a minute",
            minute: "about a minute",
            minutes: "%d minutes",
            hour: "about an hour",
            hours: "%d hours",
            day: "a day",
            days: "%d days",
            week: "about a week",
            weeks: "%d weeks",
            month: "about a month",
            months: "%d months",
            year: "about a year",
            years: "%d years"
        };
        let template = function (t, n) {
            return templates[t] && templates[t].replace(/%d/i, Math.abs(Math.round(n)));
        };

        return templates.prefix + (
            seconds < 60 && template('seconds', seconds) ||
            minutes <= 1 && template('minute', minutes) ||
            minutes < 60 && template('minutes', minutes) ||
            hours <= 1 && template('hour', hours) ||
            hours < 24 && template('hours', hours) ||
            days <= 1 && template('day', days) ||
            days < 7 && template('days', days) ||
            weeks <= 1 && template('week', weeks) ||
            weeks < 4.3 && template('weeks', weeks) ||
            months <= 1 && template('month', months) ||
            months < 12 && template('months', months) ||
            years <= 1 && template('year', years) ||
            template('years', years)
        ) + templates.suffix;
    },
    preg_replace: function (pattern, replacement, variable) {
        return variable.replace(pattern, replacement);
    },
    mt_rand: function (min, max) {
        return Math.floor((min + (Math.random() * ((max - min) + 1))));
    },
    money_format: function (number, iso3) {

        return number.toLocaleString('en-' + (this.is_string(iso3) ? iso3.substr(0, 2) : 'US'), {
            style: 'currency',
            currency: (this.is_string(iso3) ? iso3 : 'USD'),
        });
    },
    number_format: function (number, decimal) {

        return number.toLocaleString(undefined, {
            minimumFractionDigits: decimal ? decimal : 0,
            maximumFractionDigits: decimal ? decimal : 0
        });
    },
    empty: function (variable) {
        return (
            variable === false ||
            variable === null ||
            (this.is_object(variable) &&
                Object.keys(variable).length === 0) ||
            (this.is_array(variable) &&
                variable.length === 0) ||
            variable.toString() === "0" ||
            variable.toString() === "" ||
            variable.toString() === " "
        );
    },
    char_count: function (variable, character) {
        let count = 0, arr = this.explode("", variable), size = arr.length;
        for (let x = 0; x < size; x++) {
            if (arr[x] === character) count++;
        }
        return count;
    },
    intval: function (variable) {
        return parseInt(variable);
    },
    strpos: function (variable, substring, offset) {
        variable = variable.toString();
        substring = substring.toString();
        offset = parseInt(offset);
        if (offset > 0) {
            variable = variable.substring(offset, variable.length);
        }
        return (variable.search(substring) >= 0);
    }
};