/**
 *
 * @param object
 * @constructor
 */
let Validator = function (object) {

    /**
     *
     * @type {{}}
     */
    let formObject = {};

    /**
     *
     * @type {{}}
     */
    let formNode = {};

    /**
     *
     * @type {Array}
     */
    let condition = [];

    /**
     *
     * @param variable
     * @returns {boolean}
     */
    const isObject = (variable) => (typeof variable === "object" || variable instanceof Object);

    /**
     *
     * @param variable
     * @returns {boolean}
     */
    const isString = (variable) => (typeof variable === 'string' || variable instanceof String);

    /**
     *
     * @param variable
     * @returns {boolean}
     */
    const isArray = (variable) => Array.isArray(variable);

    /**
     *
     * @param variable
     * @returns {boolean}
     */
    const isUndefined = (variable) => typeof variable === "undefined";

    /***
     *
     * @param variable
     * @returns {boolean}
     */
    const isEmpty = (variable) => {
        if (isUndefined(variable)) return true;
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
     * @param object
     */
    const setFormObject = (object) => {
        for (let x in object) {
            if (object.hasOwnProperty(x)) {
                switch (object[x].tagName) {
                    case "INPUT":
                        switch (object[x].type) {
                            case "radio":
                                formObject[object[x].name] = (object[x].checked === true ?
                                    object[x].value : (!isEmpty(formObject[object[x].name]) ?
                                        formObject[object[x].name] : ""));
                                break;
                            case "checkbox":
                                formObject[object[x].name] = (object[x].checked === true ?
                                    object[x].value : (!isEmpty(formObject[object[x].name]) ?
                                        formObject[object[x].name] : ""));
                                break;
                            default:
                                formObject[object[x].name] = object[x].value;
                                break;
                        }
                        break;
                    case "TEXTAREA":
                        formObject[object[x].name] = object[x].value;
                        break;
                    case "SELECT":
                        let options = object[x].options, value = null;
                        for (let z in options)
                            if (options.hasOwnProperty(z))
                                if (options[z].selected === true) value = options[z].value;
                        formObject[object[x].name] = value;
                        break;
                }
            }
        }
    };

    /**
     *
     * @returns {{}}
     */
    this.getFormObject = () => formObject;

    /**
     *
     * @param node
     */
    const setFormNode = (node) => {
        formNode = node;
    };

    /**
     *
     * @returns {{}}
     */
    this.getFormNode = () => formNode;

    /**
     *
     * @param tagName
     * @returns {*}
     */
    this.getFormNodeByTagName = (tagName) => {
        for (let x in formNode)
            if (formNode.hasOwnProperty(x))
                if (formNode[x].name === tagName) return formNode[x];
        return null;
    };

    /**
     *
     * @param tagName
     * @returns {*}
     */
    this.getValue = (tagName) => {
        if (!isString(tagName)) throw "The 'getValue' method expects a string as its tagName";
        if (isUndefined(this.getFormObject()[tagName])) throw tagName + " is undefined";
        return this.getFormObject()[tagName];
    };

    /**
     *
     * @param tagName
     * @returns {Condition}
     */
    this.validate = (tagName) => {

        if (!isString(tagName)) throw "The 'validate' method expects a string as its tagName";
        if (isUndefined(this.getFormObject()[tagName])) throw tagName + " is undefined";

        if (isUndefined(condition[tagName]))
            condition[tagName] = new Condition(tagName, this.getValue(tagName));

        return condition[tagName];
    };

    /**
     *
     * @param tagName
     * @returns {FileCondition}
     */
    this.validateFile = (tagName) => {

        if (!isString(tagName)) throw "The 'validate' method expects a string as its tagName";
        if (isUndefined(this.getFormObject()[tagName])) throw tagName + " is undefined";

        if (isUndefined(condition[tagName]))
            condition[tagName] = new FileCondition(this.getFormNodeByTagName(tagName)['files'], tagName);

        return condition[tagName];
    };

    /**
     *
     * @param event
     * @param tagName
     * @param callback
     * @returns {Condition}
     */
    this.on = (event, tagName, callback) => {

        if (!isString(event)) throw "The validator 'on' method expects a string as its event name";
        if (!isString(tagName)) throw "The validator 'on' method expects a string as its tag name";
        if (isUndefined(this.getFormObject()[tagName])) throw tagName + " is an invalid tag name";
        if (!(callback instanceof Function)) throw "The validator 'on' method expects a callback function";

        let node = this.getFormNode(), x;

        for (x in node) {
            if (node.hasOwnProperty(x)) {
                if (node[x].name === tagName) {
                    if (isUndefined(node[x][('on' + event)])) throw event + " is an invalid event when " +
                        "passed to the validator 'on' method";
                    node[x][('on' + event)] = function (e) {
                        formObject[tagName] = this.value;
                        callback(e);
                    };
                    break;
                }
            }
        }

        if (isUndefined(condition[tagName])) condition[tagName] = new Condition(tagName, this.getValue(tagName));

        return condition[tagName];
    };

    /**
     *
     * @param tagName
     * @return {boolean}
     */
    this.isset = (tagName) => !isUndefined(this.getFormObject()[tagName]);

    /**
     *
     * @returns {boolean}
     */
    this.hasError = () => {
        for(let x in condition)
            if (condition.hasOwnProperty(x))
                if (condition[x].hasError()) return true;
        return false;
    };

    /**
     *
     * @returns {number}
     */
    this.totalError = () => {
        let count = 0;
        for (let x in condition)
            if (condition.hasOwnProperty(x))
                if (condition[x].hasError()) count++;
        return count;
    };

    /**
     *
     * @param tagName
     * @returns {boolean}
     */
    this.isEmpty = (tagName) => {

        if (!isUndefined(condition[tagName]))
            condition[tagName] = new Condition(tagName, this.getValue(tagName));

        return isEmpty(this.getValue(tagName));
    };

    /**
     *
     * @param tagName
     * @returns {boolean}
     */
    this.hasConditionError = (tagName) => {

        if (!isUndefined(condition[tagName]))
            condition[tagName] = new Condition(tagName, this.getValue(tagName));

        return !condition[tagName].hasError();
    };

    this.getErrors = () => {
        let errors = [], x;
        for (x in condition)
            if (condition.hasOwnProperty(x))
                if (condition[x].hasError())
                    errors[condition[x].getTagName()] = condition[x].getError();
        return errors;
    };

    /**
     *
     * @returns {Array}
     */
    this.getErrorsFlat = () => {
        let list = [], x;
        for (x in condition) {
            if (condition.hasOwnProperty(x)) {
                if (condition[x].hasError()) {
                    for (let z in condition[x].getError()) {
                        if (condition[x].getError().hasOwnProperty(z)) {
                            if (!isUndefined(list[condition[x].getTagName()])) continue;
                            list[condition[x].getTagName()] = condition[x].getError()[z];
                        }
                    }
                }
            }
        }
        return list;
    };

    /**
     *
     * @param tagName
     * @param error
     * @returns {Condition}
     */
    this.addError = (tagName, error) => {

        if (isUndefined(condition[tagName]))
            condition[tagName] = new Condition(tagName, this.getValue(tagName));

        condition[tagName].addError(error);

        return condition[tagName];
    };

    /**
     *
     * @param tagName
     * @param errors
     * @returns {Condition}
     */
    this.addErrors = (tagName, errors) => {
        if (!isArray(errors)) throw "The 'addErrors' method expects its errors to be an array";
        for (let x in errors)
            if (errors.hasOwnProperty(x)) this.addError(tagName, errors[x]);
        return condition[tagName];
    };

    /**
     *
     * @param tagName
     * @param condition
     * @returns {Condition|FileCondition}
     */
    this.addCondition = (tagName, condition) => {
        if (!condition instanceof Condition) throw "The 'addCondition' methods expects its " +
        "condition parameter to an instance of Condition";
        condition[tagName] = condition;
        return condition[tagName];
    };

    /**
     *
     * @param tagName
     * @returns {Condition}
     */
    this.getCondition = (tagName) => {
        if (isUndefined(condition[tagName]))
            condition[tagName] = new Condition(tagName, this.getValue(tagName));
        return condition[tagName];
    };

    /**
     *
     * @returns {Array}
     */
    this.getConditionFlat = () => condition;

    /**
     *
     * @param object
     * @constructor
     */
    ((object) => {
        if (!isObject(object[0])) throw "Validator can only be instantiated " +
        "by passing a form object to it constructor";
        setFormObject(object[0]);
        setFormNode(object[0]);
    })(object);

};