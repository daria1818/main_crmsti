<?php

namespace Pwd\Helpers;

final class StringHelper
{
    public static function TruncateSentence($body, $sentencesToDisplay = 2, $count = 165)
    {
        $nakedBody = preg_replace('/\s+/', ' ', strip_tags($body));
        $sentences = preg_split('/(\.|\?|\!)(\s)/', $nakedBody);

        if (count($sentences) <= $sentencesToDisplay)
            return $nakedBody;

        $stopAt = 0;
        foreach ($sentences as $i => $sentence) {
            $stopAt += strlen($sentence);

            if ($i >= $sentencesToDisplay - 1)
                break;
        }

        $stopAt += ($sentencesToDisplay * 2);

        $str = trim(substr($nakedBody, 0, $stopAt));
        if (strlen($str) >= $count && $sentencesToDisplay > 1) {
            $str = self::TruncateSentence($body, $sentencesToDisplay - 1);
        }
        return $str;
    }
}
