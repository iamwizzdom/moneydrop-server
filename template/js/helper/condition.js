/**
 *
 * @param tag
 * @param value
 * @constructor
 */
let Condition = function (tag, value) {

    /**
     *
     * @type {string}
     */
    let tagName = "";

    /**
     *
     * @type {string}
     */
    let tagValue = "";

    /**
     *
     * @type {Array}
     */
    let errors = [];

    /**
     *
     * @param tag
     */
    const setTagName = (tag) => {
        tagName = tag;
    };

    /**
     *
     * @param value
     */
    const setTagValue = (value) => {
        tagValue = value
    };

    /**
     *
     * @returns {string}
     */
    this.getTagName = () => tagName;

    /**
     *
     * @returns {string}
     */
    this.getTagValue = () => tagValue;

    /***
     *
     * @param variable
     * @returns {boolean}
     */
    const isEmpty = (variable) => {
        if (typeof variable === "undefined") return true;
        return (
            variable === false
            || variable === null
            || variable.toString() === "0"
            || variable.toString() === ""
            ||  variable.toString() === " "
        );
    };

    /**
     *
     * @param variable
     * @returns {boolean}
     */
    const isArray = (variable) => Array.isArray(variable);

    /**
     *
     * @param variable
     * @returns {string}
     */
    const ucFirst = (variable) => {
        variable = variable.trim();
        return (variable.substring(0, 1).toUpperCase() +
            variable.substring(1, variable.length).toLowerCase());
    };

    /**
     *
     * @param variable
     * @returns {string}
     */
    const formatCamelCase = (variable) => {
        variable = variable.replace(/_/g, " "); variable = variable.replace(/-/g, " ");
        let upperCaseArr = variable.match((new RegExp("[A-Z]", "g"))), x, newString = "", last;
        if (upperCaseArr) {
            for (x in upperCaseArr) {
                if (upperCaseArr.hasOwnProperty(x)) {
                    newString += (!isEmpty(newString) ? " " : "") +
                        variable.substring(newString.length, variable.indexOf(upperCaseArr[x]));
                    last = upperCaseArr[x];
                }
            }
            newString += (!isEmpty(newString) ? " " : "") +
                variable.substring(variable.indexOf(last), variable.length);
        }
        return ucFirst((isEmpty(newString) ? variable : newString.trim()));
    };


    /**
     *
     * @returns {Array}
     */
    this.getError = () => errors;

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.addError = (error) => {
        if (isEmpty(error))
            errors.push("Value for " + formatCamelCase(this.getTagName()) +
                " does not seem to be valid when '" +
                this.getTagValue() + "' value is given");

        errors.push(error);
        return this;
    };

    /**
     *
     * @returns {Number}
     */
    this.hasError = () => errors.length;

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isBlank = (error) => {
        if (isEmpty(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isAlphaNumeric = (error) => {
        if (!/^[a-zA-Z0-9]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isChar = (error) => {
        if (!/^[a-zA-Z]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isDate = (error) => {
        let date = Date.parse(this.getTagValue());
        if (!date) this.addError(error);
        return this;
    };

    /**
     *
     * @param compare
     * @param error
     * @returns {Condition}
     */
    this.isDateGrater = (compare, error) => {
        let date = Date.parse(this.getTagValue());
        if (!date) this.addError(error);
        if (date > Date.parse(compare)) this.addError(error);
        return this;
    };

    /**
     *
     * @param compare
     * @param error
     * @returns {Condition}
     */
    this.isDateLess = (compare, error) => {
        let date = Date.parse(this.getTagValue());
        if (!date) this.addError(error);
        if (date < Date.parse(compare)) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isUsername = (error) => {
        if (!/^[a-zA-Z0-9._]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isEmail = (error) => {
        let expr = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
        if (!expr.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param variable
     * @param error
     * @returns {Condition}
     */
    this.isIdentical = (variable, error) => {
        let value = this.getTagValue();
        if (isArray(variable)) {

            for (let x in variable) {
                if (variable.hasOwnProperty(x)) {
                    if (variable[x].toString() !== value.toString()) {
                        this.addError(error);
                        break;
                    }
                }
            }

        } else if (variable.toString() !== value.toString()) this.addError(error);
        return this;
    };

    /**
     *
     * @param variable
     * @param error
     * @returns {Condition}
     */
    this.isNotIdentical = (variable, error) => {
        let value = this.getTagValue();
        if (isArray(variable)) {

            let count = 0, x;

            for (x in variable) {
                if (variable.hasOwnProperty(x)) {
                    if (variable[x].toString() === value.toString()) {
                        count++;
                    }
                }
            }

            if (count === variable.length) this.addError(error);

        } else if (variable.toString() === value.toString()) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isPhoneNumber = (error) => {
        if (!/^[+0-9]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isNumberFormat = (error) => {
        if (!/^[0-9.,]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isNumber = (error) => {
        if (!/^[0-9]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {Condition}
     */
    this.isInteger = (error) => {
        if (!/^[0-9-]+$/.test(this.getTagValue())) this.addError(error);
        return this;
    };

    /**
     *
     * @param max
     * @param error
     * @returns {Condition}
     */
    this.hasMaxWord = (max, error) => {
        let value = this.getTagValue(), words = value.split(" ");
        if (words.length > max) this.addError(error);
        return this;
    };

    /**
     *
     * @param min
     * @param error
     * @returns {Condition}
     */
    this.hasMinWord = (min, error) => {
        let value = this.getTagValue(), words = value.split(" ");
        if (words.length < min) this.addError(error);
        return this;
    };

    /**
     *
     * @param max
     * @param error
     * @returns {Condition}
     */
    this.hasMaxLength = (max, error) => {
        let value = this.getTagValue();
        if (value.length > max) this.addError(error);
        return this;
    };

    /**
     *
     * @param min
     * @param error
     * @returns {Condition}
     */
    this.hasMinLength = (min, error) => {
        let value = this.getTagValue();
        if (value.length < min) this.addError(error);
        return this;
    };

    /**
     *
     * @param tag
     * @param value
     * @constructor
     */
    ((tag, value) => {
        setTagName(tag);
        setTagValue(value);
    })(tag, value);

};