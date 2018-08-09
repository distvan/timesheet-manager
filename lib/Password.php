<?php
namespace DotLogics;

/** 
 * Password Handling class
 * 
 * @author Istvan Dobrentei
 * @copyright DotLogics Hungary Kft.
 * @url https://www.dotlogics.hu
 * 
 */
class Password
{
    /**
    *   encode the input password
    *	input plain text password
    *   return encoded password    
    */
    
    public function encode($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
    *     check the input user password validity
    *	  input plain text password and stored encoded password
    *	  return true or false
    */
    
    public function isValid($password, $stored_value)
    {
        return password_verify($password, $stored_value);
    }
	
	/*
		$PwdType can be one of these:
        test .. .. .. always returns the same password = "test"
        any  .. .. .. returns a random password, which can contain strange characters
        alphanum . .. returns a random password containing alphanumerics only
        standard . .. same as alphanum, but not including l10O (lower L, one, zero, upper O)
    */

    public function createRandomPassword($PwdLength = 8, $PwdType = 'standard')
    {
        $Ranges = '';

        if('test' == $PwdType)
        {
            return 'test';
        }
        elseif('standard' == $PwdType)
        {
            $Ranges = '65-78,80-90,97-107,109-122,50-57';
        }
        elseif('alphanum' == $PwdType)
        {
            $Ranges = '65-90,97-122,48-57';
        }
        elseif('any' == $PwdType)
        {
            $Ranges = '40-59,61-91,93-126';
        }

        if($Ranges <> '')
        {
            $Range = explode(',',$Ranges);
            $NumRanges = count($Range);
            mt_srand(time()); //not required after PHP v4.2.0
            $p = '';
            for ($i = 1; $i <= $PwdLength; $i++)
            {
                $r = mt_rand(0, $NumRanges-1);
                list($min,$max) = explode('-', $Range[$r]);
                $p .= chr(mt_rand($min, $max));
            }

            return $p;
        }
    }

    //TODO check how is strong the password
}

?>
