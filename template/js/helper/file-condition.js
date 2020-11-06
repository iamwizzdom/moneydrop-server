let FileCondition = function (fileObject, tagName) {

    /**
     *
     * @type {string}
     */
    let name = "";

    /**
     *
     * @type {Array}
     */
    let errors = [];

    /**
     *
     * @type {Array}
     */
    let allowedExtension = [];

    /**
     *
     * @type {Array}
     */
    let allowedFileType = [];

    /**
     *
     * @type {number}
     */
    let maxFileSize = 0;

    /**
     *
     * @type {string}
     */
    let fileName = "";

    /**
     *
     * @type {string}
     */
    let extension = "";

    /**
     *
     * @type {string}
     */
    let fileType = "";

    /**
     *
     * @type {number}
     */
    let fileSize = 0;

    /**
     *
     * @type {boolean}
     */
    let isNullFile = false;

    /***
     *
     * @param variable
     * @returns {boolean}
     */
    let isEmpty = (variable) => {
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
     * @returns {Array}
     */
    this.getError = () => errors;

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
     * @param variable
     * @returns {boolean}
     */
    const isArray = (variable) => Array.isArray(variable);

    /**
     *
     * @param array
     * @param search
     * @returns {boolean}
     */
    const inArray = (array, search) => {
        if (!isArray(array)) throw "inArray expects" +
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
    };

    /**
     *
     * @param variable
     */
    const isUndefined = (variable) => typeof variable === "undefined";

    /**
     *
     * @param error
     * @returns {FileCondition}
     */
    this.addError = (error) => {
        if (isEmpty(error))
            errors.push("Value for " + formatCamelCase(this.getTagName()) +
                " does not seem to be valid when '" +
                this.getValue() + "' value is given");

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
     * @param extension
     * @returns {FileCondition}
     */
    this.setAllowedExtension = (extension) => {
        if (!isArray(extension)) throw "The 'setAllowedExtension' method " +
        "expects an array of allowed extensions, but got " + typeof extension;
        allowedExtension = extension;
        return this;
    };

    /**
     *
     * @param fileType
     * @returns {FileCondition}
     */
    this.setAllowedFileType = (fileType) => {
        if (!isArray(fileType)) throw "The 'setAllowedFileType' method " +
        "expects an array of allowed file types, but got " + typeof fileType;
        allowedFileType = fileType;
        return this;
    };

    /**
     *
     * @param size
     */
    this.setMaxFileSize = (size) => {
        if (!(!isNaN(size))) throw "The 'setMaxFileSize' method " +
        "expects a numeric mega byte size, but got " + typeof size;
        maxFileSize = size;
        return this;
    };

    /**
     *
     * @returns {number}
     */
    this.getMaxFileSize = () => maxFileSize;

    /**
     *
     * @returns {Array}
     */
    this.getAllowedExtension = () => allowedExtension;

    /**
     *
     * @returns {Array}
     */
    this.getAllowedFileType = () => allowedFileType;

    /**
     *
     * @param tagName
     */
    let setTagName = (tagName) => {
        name = tagName;
    };

    /**
     *
     * @returns {string}
     */
    this.getTagName = () => name;

    /**
     *
     * @param name
     */
    let setFileName = (name) => {
        fileName = name;
    };

    /**
     *
     * @returns {string}
     */
    this.getFileName = () => fileName;

    /**
     *
     * @param ext
     */
    let setExtension = (ext) => {
        extension = ext;
    };

    /**
     *
     * @returns {string}
     */
    this.getExtension = () => extension;

    /**
     *
     * @param type
     */
    let setFileType = (type) => {
        fileType = type;
    };

    /**
     *
     * @returns {string}
     */
    this.getFileType = () => fileType;

    /**
     *
     * @param size
     */
    let setFileSize = function (size) {
        fileSize = size;
    };

    /**
     *
     * @returns {number}
     */
    this.getFileSize = () => fileSize;

    /**
     *
     * @param status
     */
    let setIsNullFile = (status) => {
        isNullFile = status;
    };

    /**
     *
     * @returns {boolean}
     */
    this.isNullFile = () => isNullFile;

    /**
     *
     * @param error
     * @returns {FileCondition}
     */
    this.isValidExtension = (error) => {
        if (isNullFile !== false) return this;
        if (!inArray(this.getAllowedExtension(), this.getExtension())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {FileCondition}
     */
    this.isValidFileType = (error) => {
        if (isNullFile !== false) return this;
        if (!inArray(this.getAllowedFileType(), this.getFileType())) this.addError(error);
        return this;
    };

    /**
     *
     * @param error
     * @returns {FileCondition}
     */
    this.isValidFileSize = function (error) {
        if (isNullFile !== false) return this;
        if (this.getFileSize() > this.getMaxFileSize()) this.addError(error);
        return this;
    };

    /**
     *
     * @param tag
     * @param value
     * @constructor
     */
    ((file, tagName) => {
        if (isUndefined(file)) {
            setIsNullFile(true);
            return false;
        }
        let name = file.name;
        setTagName(tagName);
        setFileName(name);
        setExtension(
            name.substring(
                (name.lastIndexOf('.') + 1),
                name.length
            )
        );
        setFileType(file.type);
        setFileSize(file.size);
    })(fileObject[0], tagName);
};