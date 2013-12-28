var php = {
    in_array: function (needle, haystack, argStrict) {
        var key = '',
            strict = !! argStrict;

        if (strict) {
            for (key in haystack) {
                if (haystack[key] === needle) {
                    return true;
                }
            }
        } else {
            for (key in haystack) {
                if (haystack[key] === needle) {
                    return true;
                }
            }
        }
        return false;
    },
    ucfirst: function (str) {
        str += '';
        var f = str.charAt(0).toUpperCase();
        return f + str.substr(1);
    }
};