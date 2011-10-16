<?php
/**
 * Class MrClay_Matcher
 * @package Minify
 */

/**
 * Port of java.util.regex.Matcher
 *
 * @package Minify
 * @throws InvalidArgumentException|RuntimeException
 * @author Stephen Clay <steve@mrclay.org>
 */
class MrClay_Matcher {

    /**
     * @var MrClay_Pattern The Pattern object that created this Matcher.
     */
    protected $parentPattern;

    /**
     * @var array The storage used by groups. They may contain invalid values if a group was skipped during the matching.
     */
    protected $groups = array();

    /**
     * @var int The beginning of the range within the sequence that is to be matched. Anchors will match at these
     * "hard" boundaries. Changing the region changes these values.
     */
    protected $from;

    /**
     * @var int The end of the range within the sequence that is to be matched. Anchors will match at these
     * "hard" boundaries. Changing the region changes these values.
     */
    protected $to;

    /**
     * @var string The original string being matched.
     */
    protected $text;

    /**
     * mode used for matching all the input.
     */
    const ENDANCHOR = 1;

    /**
     * mode used when a match does not have to consume all of the input.
     */
    const NOANCHOR = 0;

    /**
     * @var int Matcher state used by the last node. NOANCHOR is used when a match does not have to consume
     * all of the input. ENDANCHOR is the mode used for matching all the input.
     */
    protected $acceptMode = self::NOANCHOR;

    /**
     * @var int Beginning of the range of string that last matched the pattern. If the last match failed then $first
     * is -1;
     */
    protected $first = -1;

    /**
     * @var int $last initially holds 0 then it holds the index of the end of the last match (which is where the next
     * search starts).
     */
    protected $last = 0;

    /**
     * @var int The end index of what matched in the last match operation.
     */
    protected $oldLast = -1;

    /**
     * @var int The index of the last position appended in a substitution.
     */
    protected $lastAppendPosition = 0;

    /**
     * @var bool Boolean indicating whether or not more input could change the results of the last match.
     *
     * If hitEnd is true, and a match was found, then more input might cause a different match to be found.
     * If hitEnd is true and a match was not found, then more input could cause a match to be found.
     * If hitEnd is false and a match was found, then more input will not change the match.
     * If hitEnd is false and a match was not found, then more input will not cause a match to be found.
     */
    protected $hitEnd;

    /**
     * @var bool Boolean indicating whether or not more input could change a positive match into a negative one.
     *
     * If requireEnd is true, and a match was found, then more input could cause the match to be lost.
     * If requireEnd is false and a match was found, then more input might change the match but the match won't be lost.
     * If a match was not found, then requireEnd has no meaning.
     */
    protected $requireEnd;

    /**
     * @var bool If transparentBounds is true then the boundaries of this matcher's region are transparent to
     * lookahead, lookbehind, and boundary matching constructs that try to see beyond them.
     */
    protected $transparentBounds = false;

    /**
     * @var bool If anchoringBounds is true then the boundaries of this matcher's region match anchors such as ^ and $.
     */
    protected $anchoringBounds = true;

    /**
     * @param MrClay_Pattern $parent
     * @param string $text
     */
    public function __construct(MrClay_Pattern $parent, $text)
    {
        $this->parentPattern = $parent;
        $this->text = $text;

        // Put fields into initial states
        $this->reset();
    }

    /**
     * Returns the pattern that is interpreted by this matcher.
     * @return MrClay_Pattern
     */
    public function pattern()
    {
        return $this->parentPattern;
    }

    /**
     * Returns the match state of this matcher
     * @return MrClay_MatchResult
     */
    public function toMatchResult()
    {
        // @todo
    }

    /**
     * Changes the Pattern that this Matcher uses to find matches with.
     * @param MrClay_Pattern $newPattern
     * @return MrClay_Matcher
     */
    public function usePattern(MrClay_Pattern $newPattern)
    {
        $this->parentPattern = $newPattern;
        $this->groups = array();
        return $this;
    }

    /**
     * Attempts to match the entire region against the pattern.
     * @return bool
     */
    public function matches()
    {
        return $this->match($this->from, self::ENDANCHOR);
    }

    /**
     * Attempts to find the next subsequence of the input sequence that matches the pattern. If $start is specified,
     * this resets this matcher and then attempts to find the next subsequence of the input sequence that matches the
     * pattern, starting at the specified index.
     * @param int $start
     * @return bool
     */
    public function find($start = null)
    {
        if ($start !== null) {
            $this->reset();
            return $this->search($start);
        }

        $nextSearchIndex = $this->last;
        if ($nextSearchIndex == $this->first)
            $nextSearchIndex++;

        // If next search starts before region, start it at region
        if ($nextSearchIndex < $this->from)
            $nextSearchIndex = $this->from;

        // If next search starts beyond region then it fails
        if ($nextSearchIndex > $this->to) {
            $this->groups = array();
            return false;
        }
        return $this->search($nextSearchIndex);
    }

    /**
     * Resets this matcher with a new input sequence.
     * @param $input
     * @return MrClay_Matcher
     */
    public function reset($input = null)
    {
        if ($input !== null) {
            $this->text = $input;
        }
        $this->first = -1;
        $this->last = 0;
        $this->oldLast = -1;
        $this->groups = array();
        $this->lastAppendPosition = 0;
        $this->from = 0;
        $this->to = strlen($this->text);
        return $this;
    }

    /**
     * Returns the start index of the previous match. If $group is given, this returns the start index of the
     * subsequence captured by the given group during the previous match operation.
     * @param int $group
     * @return int
     */
    public function start($group = null)
    {
        if ($group !== null) {
            return isset($this->groups[$group * 2]) ? $this->groups[$group * 2] : -1;
        }
        return $this->first;
    }

    /**
     * Returns the offset after the last character matched. If $group is given, this returns the offset after the last
     * character of the subsequence captured by the given group during the previous match operation
     * @param int $group
     * @return int
     */
    public function end($group = null)
    {
        if ($group !== null) {
            return isset($this->groups[$group * 2 + 1]) ? $this->groups[$group * 2 + 1] : -1;
        }
        return $this->last;
    }

    /**
     * Returns the input subsequence matched by the previous match. If $group is given, this returns the input
     * subsequence captured by the given group during the previous match operation.
     * @param int $group
     * @return string
     */
    public function group($group = null)
    {
        if ($group !== null) {
            return substr(
                $this->text,
                $this->groups[$group * 2],
                ($this->groups[$group * 2 + 1] - $this->groups[$group * 2]));
        }
        return $this->group(0);
    }

    /**
     * Returns the number of capturing groups in this matcher's pattern.
     * @return int
     */
    public function groupCount()
    {
        return $this->parentPattern->capturingGroupCount - 1;
    }

    /**
     * Attempts to match the input sequence, starting at the beginning of the region, against the pattern.
     * @return bool
     */
    public function lookingAt()
    {
        return $this->match($this->from, self::NOANCHOR);
    }

    /**
     * Returns a literal replacement String for the specified String.
     * @param string $s
     * @return string
     */
    public function quoteReplacement($s)
    {
        return preg_quote($s, '/');
    }

    /**
     * Implements a non-terminal append-and-replace step.
     * @param string $sb
     * @param string $replacement
     * @return MrClay_Matcher
     */
    public function appendReplacement(&$sb, $replacement)
    {
        // If no match, return error
        if ($this->first < 0)
            throw new RuntimeException("No match available");

        // Process substitution string to replace group references with groups
        $cursor = 0;
        $result = '';

        while ($cursor < strlen($replacement)) {
            $nextChar = $replacement[$cursor];
            if ($nextChar == '\\') {
                $cursor++;
                $nextChar = $replacement[$cursor];
                $result .= $nextChar;
                $cursor++;
            } else if ($nextChar == '$') {
                // Skip past $
                $cursor++;
                // The first number is always a group
                $refNum = (int) $replacement[$cursor] - '0';
                if (($refNum < 0) || ($refNum > 9))
                    throw new InvalidArgumentException(
                            "Illegal group reference");
                $cursor++;

                // Capture the largest legal group string
                $done = false;
                while (!$done) {
                    if ($cursor >= strlen($replacement)) {
                        break;
                    }
                    $nextDigit = $replacement[$cursor] - '0';
                    if (($nextDigit < 0) || ($nextDigit > 9)) { // not a number
                        break;
                    }
                    $newRefNum = ($refNum * 10) + $nextDigit;
                    if ($this->groupCount() < $newRefNum) {
                        $done = true;
                    } else {
                        $refNum = $newRefNum;
                        $cursor++;
                    }
                }
                // Append group
                if ($this->start($refNum) != -1 && $this->end($refNum) != -1)
                    $result .= substr(
                            $this->text,
                            $this->start($refNum),
                            $this->end($refNum) - $this->start($refNum)
                    );
            } else {
                $result .= $nextChar;
                $cursor++;
            }
        }
        // Append the intervening text
        $sb .= substr($this->text, $this->lastAppendPosition, $this->first - $this->lastAppendPosition);
        // Append the match substitution
        $sb .= $result;

        $this->lastAppendPosition = $this->last;
        return $this;
    }

    /**
     * Implements a terminal append-and-replace step.
     * @param string $sb
     * @return string
     */
    public function appendTail(&$sb)
    {
        $sb .= substr($this->text, $this->lastAppendPosition);
        return $sb;
    }

    /**
     * Replaces every subsequence of the input sequence that matches the pattern with the given replacement string.
     * @param string $replacement
     * @return string
     */
    public function replaceAll($replacement)
    {
        $this->reset();
        $result = $this->find();
        if ($result) {
            $sb = '';
            do {
                $this->appendReplacement($sb, $replacement);
                $result = $this->find();
            } while ($result);
            $this->appendTail($sb);
            return $sb;
        }
        return $this->text;
    }

    /**
     * Replaces the first subsequence of the input sequence that matches the pattern with the given replacement string.
     * @param string $replacement
     * @return string
     */
    public function replaceFirst($replacement)
    {
        $this->reset();
        if (!$this->find())
            return $this->text;
        $sb = '';
        $this->appendReplacement($sb, $replacement);
        $this->appendTail($sb);
        return $sb;
    }

    /**
     * Sets the limits of this matcher's region.
     * @param int $start
     * @param int $end
     * @return MrClay_Matcher
     */
    public function region($start, $end)
    {
        $this->reset();
        $this->from = $start;
        $this->to = $end;
        return $this;
    }

    /**
     * Reports the start index of this matcher's region.
     * @return int
     */
    public function regionStart()
    {
        return $this->from;
    }

    /**
     * Reports the end index (exclusive) of this matcher's region.
     * @return int
     */
    public function regionEnd()
    {
        return $this->to;
    }

    /**
     * Queries the transparency of region bounds for this matcher.
     * @return bool
     */
    public function hasTransparentBounds()
    {
        return $this->transparentBounds;
    }

    /**
     * Sets the transparency of region bounds for this matcher.
     * @param bool $b
     * @return MrClay_Matcher
     */
    public function useTransparentBounds($b)
    {
        $this->transparentBounds = $b;
        return $this;
    }

    /**
     * Queries the anchoring of region bounds for this matcher.
     * @return bool
     */
    public function hasAnchoringBounds()
    {
        return $this->anchoringBounds;
    }

    /**
     * Sets the anchoring of region bounds for this matcher.
     * @param bool $b
     * @return MrClay_Matcher
     */
    public function useAnchoringBounds($b)
    {
        $this->anchoringBounds = $b;
        return $this;
    }

    /**
     * Returns true if the end of input was hit by the search engine in the last match operation performed by this
     * matcher.
     * @return bool
     */
    public function hitEnd()
    {
        return $this->hitEnd;
    }

    /**
     * Returns true if more input could change a positive match into a negative one.
     * @return bool
     */
    public function requireEnd()
    {
        return $this->requireEnd;
    }

    /**
     * Initiates a search to find a Pattern within the given bounds. The groups are filled with
     * default values and the match of the root of the state machine is called. The state machine
     * will hold the state of the match as it proceeds in this matcher.
     *
     * Matcher.from is not set here, because it is the "hard" boundary of the start of the search
     * which anchors will set to. The from param is the "soft" boundary of the start of the search,
     * meaning that the regex tries to match at that index but ^ won't match there. Subsequent
     * calls to the search methods start at a new "soft" boundary which is the end of the previous match.
     *
     * @param int $from
     * @return bool
     */
    protected function search($from)
    {
        $this->hitEnd = false;
        $this->requireEnd = false;
        $from = $from < 0 ? 0 : $from;
        $this->first = $from;
        $this->oldLast = $this->oldLast < 0 ? $from : $this->oldLast;
        $this->groups = array();
        $this->acceptMode = self::NOANCHOR;

        if ($this->anchoringBounds) {
            // ^ and $ should anchor the beg/end of the string given
            $result = preg_match($this->parentPattern->pattern, substr($this->text, $from), $m, PREG_OFFSET_CAPTURE);
            if ($result) {
                // adjust the offsets to account for sending a substring in
                foreach ($m as $k => $match) {
                    $m[$k][1] = $match[1] + $from;
                }
            }
        } else {
            $result = preg_match($this->parentPattern->pattern, $this->text, $m, PREG_OFFSET_CAPTURE, $from);
        }
        if ($result) {
            $this->first = $m[0][1];
            $this->last = $m[0][1] + strlen($m[0][0]);
            foreach ($m as $match) {
                $this->groups[] = $match[1];
                $this->groups[] = $match[1] + strlen($match[0]);
            }
        } else {
            $this->first = -1;
        }
        $this->oldLast = $this->last;
        return (bool) $result;
    }

    /**
     * Initiates a search for an anchored match to a Pattern within the given
     * bounds. The groups are filled with default values and the match of the
     * root of the state machine is called. The state machine will hold the
     * state of the match as it proceeds in this matcher.
     *
     * @param int $from
     * @param int $anchor
     * @return bool
     */
    protected function match($from, $anchor) {
        $this->hitEnd = false;
        $this->requireEnd = false;
        $from = $from < 0 ? 0 : $from;
        $this->first = $from;
        $this->oldLast = $this->oldLast < 0 ? $from : $this->oldLast;
        $this->groups = array();
        $this->acceptMode = $anchor;

        if ($this->anchoringBounds) {
            // ^ and $ should anchor the beg/end of the string given
            $result = preg_match($this->parentPattern->pattern, substr($this->text, $from), $m, PREG_OFFSET_CAPTURE);
            if ($result) {
                // adjust the offsets to account for sending a substring in
                foreach ($m as $k => $match) {
                    $m[$k][1] = $match[1] + $from;
                }
            }
        } else {
            $result = preg_match($this->parentPattern->pattern, $this->text, $m, PREG_OFFSET_CAPTURE, $from);
        }
        // require entire match in ENDANCHOR mode
        if ($result && ($this->acceptMode === self::ENDANCHOR) && ($m[0][0] !== substr($this->text, $from))) {
            $result = false;
        }
        if ($result) {
            $this->first = $m[0][1];
            $this->last = $m[0][1] + strlen($m[0][0]);
            foreach ($m as $match) {
                $this->groups[] = $match[1];
                $this->groups[] = $match[1] + strlen($match[0]);
            }
        } else {
            $this->first = -1;
        }
        $this->oldLast = $this->last;
        return (bool) $result;
    }
}
