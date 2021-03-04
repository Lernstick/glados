
/**
 * @param str the string to search
 * @param pos the postion
 * @return the postion start and end
 */
function getWordAt(str, pos) {

    // Perform type conversions.
    str = String(str);
    pos = Number(pos) >>> 0;

    // Search for the word's beginning and end.
    var start = str.slice(0, pos + 1).search(/\S+$/),
        end = str.slice(pos).search(/\s/);

    // The last and first word in the string are special cases.
    end = end < 0 ? str.length : end+pos;
    start = start < 0 ? 0 : start;

    return [start, end]; //use with slice(a, b) to extract the portion
}