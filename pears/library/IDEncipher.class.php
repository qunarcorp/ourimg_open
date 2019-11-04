<?php
/**
 * 加密、解密类，实现对数字的加密、解密
 */

class IDEncipher {
    
    private $defaultKey = 0x1B2C3D4FAE;
    private $mKey = null;
    private $low_16_mask = 0xABCD;
    private $half_shift = 16;
    private $num_rounds = 4;
    private $mRoundKeys = array();
    private $large_val = 0;
    
    public function __construct($key = '') {
        if(!empty($key)) {
            $this->mKey = $key;
        } else {
            $this->mKey = $this->defaultKey;
        }
        $this->large_val = pow(2, 32);
        $this->setKey();
    }
    
    private function setKey() {
        $this->mRoundKeys[0] = $this->mKey & $this->low_16_mask;
        $this->mRoundKeys[1] =  ~($this->mKey & $this->low_16_mask);
        $this->mRoundKeys[2] = $this->mKey >> $this->half_shift;
        $this->mRoundKeys[3] = ~($this->mKey >> $this->half_shift);
    }
    
    private function F($num, $round) {
        $num ^= $this->mRoundKeys[$round];
        $num *= $num;
        return ($num >> $this->half_shift) ^ ($num & $this->low_16_mask);
    }
    
    private function encipher($plain) {
        $rhs = $plain & $this->low_16_mask;
        $lhs = $plain >> $this->half_shift;
        for($i = 0; $i < $this->num_rounds; ++$i) {
            if($i > 0) {
                $temp = $lhs;
                $lhs = $rhs;
                $rhs = $temp;
            }
            $rhs ^= $this->F($lhs, $i);
        }
        
        $v = ($lhs << $this->half_shift) + ($rhs & $this->low_16_mask);
        if($v < 0) {
            $v += $this->large_val;
        }
        
        return $v;
    }
    
    private function decipher($cypherlong) {
        $cypher=0;
        if($cypherlong > PHP_INT_MAX){
            $cypher = (int)($cypherlong - $this->large_val);
        }else{
            $cypher = (int)$cypherlong;
        }
        
        $rhs = $cypher & $this->low_16_mask;
        $lhs = $cypher >> $this->half_shift;
        
        for ($i = 0; $i < $this->num_rounds; ++$i) {
            if ($i > 0) {
                $temp = $lhs;
                $lhs = $rhs;
                $rhs = $temp;
            }
            
            $rhs ^= $this->F($lhs, $this->num_rounds - 1 - $i);
        }
        
        return ($lhs << $this->half_shift) + ($rhs & $this->low_16_mask);
    }
    
    public function encrypt($id) {
        return $this->encipher($id);
    }
    
    public function decrypt($value) {
        return $this->decipher($value);
    }
}