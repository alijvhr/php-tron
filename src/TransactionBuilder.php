<?php
namespace Tron;

use DateTime;
use Tron\Exception\TronException;

// Web3 plugin
use Web3\Contracts\Ethabi;
use Web3\Contracts\Types\{Address, Boolean, Bytes, DynamicBytes, Integer, Str, Uinteger};

class TransactionBuilder
{
    /**
     * Create an TransactionBuilder object
     */
    public function __construct(
        /**
         * Base Tron object
         */
        protected \Tron\Tron $tron
    )
    {
    }

    /**
     * Creates a transaction of transfer.
     * If the recipient address does not exist, a corresponding account will be created on the blockchain.
     *
     * @param string|null $from
     * @param string|null $message
     * @throws TronException
     */
    public function sendTrx(string $to, float $amount, string $from = null, string $message = null): array
    {
        if ($amount < 0) {
            throw new TronException('Invalid amount provided');
        }

        if(is_null($from)) {
            $from = $this->tron->address['hex'];
        }

        $to = $this->tron->address2HexString($to);
        $from = $this->tron->address2HexString($from);

        if ($from === $to) {
            throw new TronException('Cannot transfer TRX to the same account');
        }

        $options = [
            'to_address' => $to,
            'owner_address' => $from,
            'amount' => $this->tron->toTron($amount),
        ];

        if(!is_null($message)) {
            $params['extra_data'] = $this->tron->stringUtf8toHex($message);
        }

        return $this->tron->getManager()->request('wallet/createtransaction', $options);
    }

    /**
     * Transfer Token
     *
     * @param string|null $from
     * @throws TronException
     */
    public function sendToken(string $to, int $amount, string $tokenID, string $from): array
    {
        if (!is_int($amount) || $amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        if (!is_string($tokenID)) {
            throw new TronException('Invalid token ID provided');
        }

        if ($to === $from) {
            throw new TronException('Cannot transfer tokens to the same account');
        }

        $transfer = $this->tron->getManager()->request('wallet/transferasset', [
            'owner_address' => $this->tron->address2HexString($from),
            'to_address' => $this->tron->address2HexString($to),
            'asset_name' => $this->tron->stringUtf8toHex($tokenID),
            'amount' => $amount
        ]);

        if (array_key_exists('Error', $transfer)) {
            throw new TronException($transfer['Error']);
        }
        return $transfer;
    }

    /**
     * Purchase a Token
     *
     * @param $issuerAddress
     * @param $tokenID
     * @param $amount
     * @param $buyer
     * @throws TronException
     */
    public function purchaseToken($issuerAddress, $tokenID, $amount, $buyer): array
    {
        if (!is_string($tokenID)) {
            throw new TronException('Invalid token ID provided');
        }

        if (!is_int($amount) && $amount <= 0) {
            throw new TronException('Invalid amount provided');
        }

        $purchase = $this->tron->getManager()->request('wallet/participateassetissue', [
            'to_address' => $this->tron->address2HexString($issuerAddress),
            'owner_address' => $this->tron->address2HexString($buyer),
            'asset_name' => $this->tron->stringUtf8toHex($tokenID),
            'amount' => $this->tron->toTron($amount)
        ]);

        if (array_key_exists('Error', $purchase)) {
            throw new TronException($purchase['Error']);
        }
        return $purchase;
    }

    /**
     * createToken
     *
     * @param null $issuerAddress
     * @throws TronException
     */
    public function createToken(array $options = [], $issuerAddress = null): array
    {
        $startDate = new DateTime();
        $startTimeStamp = $startDate->getTimestamp() * 1000;

        // Create default parameters in case of their absence
        if (!$options['totalSupply']) {
            $options['totalSupply'] = 0;
        }
        if (!$options['trxRatio']) {
            $options['trxRatio'] = 1;
        }
        if (!$options['tokenRatio']) {
            $options['tokenRatio'] = 1;
        }
        if (!$options['freeBandwidth']) {
            $options['freeBandwidth'] = 0;
        }
        if (!$options['freeBandwidthLimit']) {
            $options['freeBandwidthLimit'] = 0;
        }
        if (!$options['frozenAmount']) {
            $options['frozenAmount'] = 0;
        }
        if (!$options['frozenDuration']) {
            $options['frozenDuration'] = 0;
        }

        if (is_null($issuerAddress)) {
            $issuerAddress = $this->tron->address['hex'];
        }

        if(!$options['name'] || !is_string($options['name'])) {
            throw new TronException('Invalid token name provided');
        }

        if(!$options['abbreviation'] || !is_string($options['abbreviation'])) {
            throw new TronException('Invalid token abbreviation provided');
        }

        if(!is_int($options['totalSupply']) || $options['totalSupply'] <= 0) {
            throw new TronException('Invalid supply amount provided');
        }

        if(!is_int($options['trxRatio']) || $options['trxRatio'] <= 0) {
            throw new TronException('TRX ratio must be a positive integer');
        }

        if(!is_int($options['saleStart']) || $options['saleStart'] <= $startTimeStamp) {
            throw new TronException('Invalid sale start timestamp provided');
        }

        if(!is_int($options['saleEnd']) || $options['saleEnd'] <= $options['saleStart']) {
            throw new TronException('Invalid sale end timestamp provided');
        }

        if(!$options['description'] || !is_string($options['description'])) {
            throw new TronException('Invalid token description provided');
        }

        if(!is_string($options['url']) || !filter_var($options['url'], FILTER_VALIDATE_URL)) {
            throw new TronException('Invalid token url provided');
        }

        if(!is_int($options['freeBandwidth']) || $options['freeBandwidth'] < 0) {
            throw new TronException('Invalid free bandwidth amount provided');
        }

        if(!is_int($options['freeBandwidthLimit']) || $options['freeBandwidthLimit '] < 0 ||
            ($options['freeBandwidth'] && !$options['freeBandwidthLimit'])
        ) {
            throw new TronException('Invalid free bandwidth limit provided');
        }

        if(!is_int($options['frozenAmount']) || $options['frozenAmount '] < 0 ||
            (!$options['frozenDuration'] && $options['frozenAmount'])
        ) {
            throw new TronException('Invalid frozen supply provided');
        }

        if(!is_int($options['frozenDuration']) || $options['frozenDuration '] < 0 ||
            ($options['frozenDuration'] && !$options['frozenAmount'])
        ) {
            throw new TronException('Invalid frozen duration provided');
        }

        $data = [
            'owner_address' => $this->tron->address2HexString($issuerAddress),
            'name'  =>  $this->tron->stringUtf8toHex($options['name']),
            'abbr'  =>   $this->tron->stringUtf8toHex($options['abbreviation']),
            'description'   =>  $this->tron->stringUtf8toHex($options['description']),
            'url'   =>  $this->tron->stringUtf8toHex($options['url']),
            'total_supply'   => $options['totalSupply'],
            'trx_num'   => $options['trxRatio'],
            'num'   =>  intval($options['tokenRatio']),
            'start_time'   => $options['saleStart'],
            'end_time'   => $options['saleEnd'],
            'free_asset_net_limit'  => $options['freeBandwidth'],
            'public_free_asset_net_limit'   => $options['freeBandwidthLimit'],
            'frozen_supply' =>  [
                'frozen_amount' => $options['frozenAmount'],
                'frozen_days' => $options['frozenDuration'],
            ]
        ];

        if($options['precision'] && !is_nan(intval($options['precision']))) {
            $data['precision'] = intval($options['precision']);
        }

        if($options['voteScore'] && !is_nan(intval($options['voteScore']))) {
            $data['vote_score'] = intval($options['voteScore']);
        }

        return $this->tron->getManager()->request('wallet/createassetissue', $data);
    }

    /**
     * Freezes an amount of TRX.
     * Will give bandwidth OR Energy and TRON Power(voting rights) to the owner of the frozen tokens.
     *
     * @param string|null $address
     * @throws TronException
     */
    public function freezeBalance(float $amount = 0, int $duration = 3, string $resource = 'BANDWIDTH', string $address = null): array
    {
        if ($address === null || $address === '' || $address === '0') {
            throw new TronException('Address not specified');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        if (!is_float($amount)) {
            throw new TronException('Invalid amount provided');
        }

        if(!is_int($duration) && $duration < 3) {
            throw new TronException('Invalid duration provided, minimum of 3 days');
        }

        return $this->tron->getManager()->request('wallet/freezebalance', [
            'owner_address' => $this->tron->address2HexString($address),
            'frozen_balance' => $this->tron->toTron($amount),
            'frozen_duration' => $duration,
            'resource' => $resource
        ]);
    }

    /**
     * Unfreeze TRX that has passed the minimum freeze duration.
     * Unfreezing will remove bandwidth and TRON Power.
     *
     * @throws TronException
     */
    public function unfreezeBalance(string $resource = 'BANDWIDTH', string $owner_address = null): array
    {
        if(is_null($owner_address)) {
            throw new TronException('Owner Address not specified');
        }

        if (!in_array($resource, ['BANDWIDTH', 'ENERGY'])) {
            throw new TronException('Invalid resource provided: Expected "BANDWIDTH" or "ENERGY"');
        }

        return $this->tron->getManager()->request('wallet/unfreezebalance', [
            'owner_address' =>  $this->tron->address2HexString($owner_address),
            'resource' => $resource
        ]);
    }

    /**
     * Withdraw Super Representative rewards, useable every 24 hours.
     *
     * @param string|null $owner_address
     * @throws TronException
     */
    public function withdrawBlockRewards(string $owner_address = null): array
    {
        $withdraw =  $this->tron->getManager()->request('wallet/withdrawbalance', [
            'owner_address' =>  $this->tron->address2HexString($owner_address)
        ]);

        if (array_key_exists('Error', $withdraw)) {
            throw new TronException($withdraw['Error']);
        }
        return $withdraw;
    }

    /**
     * Update a Token's information
     *
     * @param $address
     * @throws TronException
     */
    public function updateToken(string $description, string $url, int $freeBandwidth = 0, int $freeBandwidthLimit = 0, $address = null): array
    {
        if(is_null($address)) {
            throw new TronException('Owner Address not specified');
        }

        if (!is_int($freeBandwidth) || $freeBandwidth < 0) {
            throw new TronException('Invalid free bandwidth amount provided');
        }

        if (!is_int($freeBandwidthLimit) || ($freeBandwidthLimit < 0 && ($freeBandwidth && !$freeBandwidthLimit))) {
            throw new TronException('Invalid free bandwidth limit provided');
        }

        return $this->tron->getManager()->request('wallet/updateasset', [
            'owner_address'      =>  $this->tron->address2HexString($address),
            'description'        =>  $this->tron->stringUtf8toHex($description),
            'url'               =>  $this->tron->stringUtf8toHex($url),
            'new_limit'         => $freeBandwidth,
            'new_public_limit'  => $freeBandwidthLimit
        ]);
    }

    /**
     * updateEnergyLimit
     *
     * @throws TronException
     */
    public function updateEnergyLimit(string $contractAddress, int $originEnergyLimit, string $ownerAddress): array
    {
        $contractAddress = $this->tron->address2HexString($contractAddress);
        $ownerAddress = $this->tron->address2HexString($ownerAddress);

        if($originEnergyLimit < 0 || $originEnergyLimit > 10000000) {
            throw new TronException('Invalid originEnergyLimit provided');
        }

        return $this->tron->getManager()->request('wallet/updateenergylimit', [
            'owner_address' =>  $this->tron->address2HexString($ownerAddress),
            'contract_address' => $this->tron->address2HexString($contractAddress),
            'origin_energy_limit' => $originEnergyLimit
        ]);
    }

    /**
     * updateSetting
     *
     * @throws TronException
     */
    public function updateSetting(string $contractAddress, int $userFeePercentage, string $ownerAddress): array
    {
        $contractAddress = $this->tron->address2HexString($contractAddress);
        $ownerAddress = $this->tron->address2HexString($ownerAddress);

        if($userFeePercentage < 0 || $userFeePercentage > 1000) {
            throw new TronException('Invalid userFeePercentage provided');
        }

        return $this->tron->getManager()->request('wallet/updatesetting', [
            'owner_address' =>  $this->tron->address2HexString($ownerAddress),
            'contract_address' => $this->tron->address2HexString($contractAddress),
            'consume_user_resource_percent' => $userFeePercentage
        ]);
    }
/**
 * Contract Balance
 * @param string $address $tron->toHex('Txxxxx');
 */
public function contractbalance($adres): array
{
	$trc20=[];
  $abi=json_decode('{"entrys": [{"constant": true,"name": "name","outputs": [{"type": "string"}],"type": "Function","stateMutability": "View"},{"name": "approve","inputs": [{"name": "_spender","type": "address"},{"name": "_value","type": "uint256"}],"outputs": [{"type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"name": "setCanApproveCall","inputs": [{"name": "_val","type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "totalSupply","outputs": [{"type": "uint256"}],"type": "Function","stateMutability": "View"},{"name": "transferFrom","inputs": [{"name": "_from","type": "address"},{"name": "_to","type": "address"},{"name": "_value","type": "uint256"}],"outputs": [{"type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "decimals","outputs": [{"type": "uint8"}],"type": "Function","stateMutability": "View"},{"name": "setCanBurn","inputs": [{"name": "_val","type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"name": "burn","inputs": [{"name": "_value","type": "uint256"}],"outputs": [{"name": "success","type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "balanceOf","inputs": [{"name": "_owner","type": "address"}],"outputs": [{"type": "uint256"}],"type": "Function","stateMutability": "View"},{"constant": true,"name": "symbol","outputs": [{"type": "string"}],"type": "Function","stateMutability": "View"},{"name": "transfer","inputs": [{"name": "_to","type": "address"},{"name": "_value","type": "uint256"}],"outputs": [{"type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "canBurn","outputs": [{"type": "bool"}],"type": "Function","stateMutability": "View"},{"name": "approveAndCall","inputs": [{"name": "_spender","type": "address"},{"name": "_value","type": "uint256"},{"name": "_extraData","type": "bytes"}],"outputs": [{"name": "success","type": "bool"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "allowance","inputs": [{"name": "_owner","type": "address"},{"name": "_spender","type": "address"}],"outputs": [{"type": "uint256"}],"type": "Function","stateMutability": "View"},{"name": "transferOwnership","inputs": [{"name": "_newOwner","type": "address"}],"type": "Function","stateMutability": "Nonpayable"},{"constant": true,"name": "canApproveCall","outputs": [{"type": "bool"}],"type": "Function","stateMutability": "View"},{"type": "Constructor","stateMutability": "Nonpayable"},{"name": "Transfer","inputs": [{"indexed": true,"name": "_from","type": "address"},{"indexed": true,"name": "_to","type": "address"},{"name": "_value","type": "uint256"}],"type": "Event"},{"name": "Approval","inputs": [{"indexed": true,"name": "_owner","type": "address"},{"indexed": true,"name": "_spender","type": "address"},{"name": "_value","type": "uint256"}],"type": "Event"},{"name": "Burn","inputs": [{"indexed": true,"name": "_from","type": "address"},{"name": "_value","type": "uint256"}],"type": "Event"}]}',true);
  $feeLimit=1000000;
  $func="balanceOf";
  $jsonData = json_decode(file_get_contents("https://apilist.tronscan.org/api/token_trc20?sort=issue_time&limit=100&start=0"),true);
  foreach($jsonData["trc20_tokens"] as $item)
  {
	  $owner=$item["contract_address"];
	  $params=["0"=>$this->tron->toHex($adres)];
  	$result = $this->tron->getTransactionBuilder()->triggerSmartContract(
  	$abi['entrys'],
	  $this->tron->toHex($owner),
  	$func,
	  $params,
  	$feeLimit,
  	$this->tron->toHex($adres),
  	0,
  	0);
    $balance_hex=$result["0"];
    $balance=(float)number_format($balance_hex->value/10 ** $item["decimals"],$item["decimals"],".","");
    if($balance>0)
  	{
      $trc20[]=[
      "name"=>$item["name"],
      "symbol"=>$item["symbol"],
      "balance"=>$balance,
      "value"=>$balance_hex->value,
      "decimals"=>$item["decimals"],
      ];
    }
  }
return $trc20;
}
    
    /**
     * Triggers smart contract
     *
     * @param string $contract $tron->toHex('Txxxxx');
     * @param array $params array("0"=>$value);
     * @param integer $feeLimit
     * @param string $address $tron->toHex('Txxxxx');
     *
     * @throws TronException
     */
    public function triggerSmartContract(mixed   $abi,
                                         string  $contract,
                                         string  $function,
                                         array   $params,
                                         Integer $feeLimit,
                                         string  $address,
                                         int     $callValue = 0,
                                         int     $bandwidthLimit = 0): mixed
    {
        $func_abi = [];
        foreach($abi as $item) {
            if(isset($item['name']) && $item['name'] === $function) {
                $func_abi = $item;
                break;
            }
        }

        if (count($func_abi) === 0) {
            throw new TronException("Function $function not defined in ABI");
        }

        if (!is_array($params)) {
            throw new TronException("Function params must be an array");
        }

        if (count($func_abi['inputs']) !== count($params)) {
            throw new TronException("Count of params and abi inputs must be identical");
        }

        if ($feeLimit > 1000000000) {
            throw new TronException('fee_limit must not be greater than 1000000000');
        }


        $inputs = array_map(fn(array $item) => $item['type'],$func_abi['inputs']);
        $signature = $func_abi['name'].'(';
        if (count($inputs) > 0) {
            $signature .= implode(',',$inputs);
        }
        $signature .= ')';

        $eth_abi = new Ethabi([
            'address' => new Address,
            'bool' => new Boolean,
            'bytes' => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int' => new Integer,
            'string' => new Str,
            'uint' => new Uinteger,
        ]);
        $parameters = substr($eth_abi->encodeParameters($func_abi, $params),2);

        $result = $this->tron->getManager()->request('wallet/triggersmartcontract', [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' =>  $address,
            'fee_limit'     =>  $feeLimit,
            'call_value'    =>  $callValue,
            'consume_user_resource_percent' =>  $bandwidthLimit,
        ]);

        if(!isset($result['result'])){
            throw new TronException('No result field in response. Raw response:'.print_r($result,true));
        }
        if(isset($result['result']['result'])) {
            if(count($func_abi['outputs']) >= 0 && isset($result['constant_result'])) {
                return $eth_abi->decodeParameters($func_abi, $result['constant_result'][0]);
            }
            return $result['transaction'];
        }
        $message = isset($result['result']['message']) ?
            $this->tron->hexString2Utf8($result['result']['message']) : '';

        throw new TronException('Failed to execute. Error:'.$message);
    }

    /**
     * Triggers constant contract
     *
     * @param string $contract $tron->toHex('Txxxxx');
     * @param array $params array("0"=>$value);
     * @param string $address $tron->toHex('Txxxxx');
     *
     * @throws TronException
     */
    public function triggerConstantContract(mixed  $abi,
                                            string $contract,
                                            string $function,
                                            array  $params = [],
                                            string $address = '410000000000000000000000000000000000000000'): mixed
    {
        $func_abi = [];
        foreach($abi as $item) {
            if(isset($item['name']) && $item['name'] === $function) {
                $func_abi = $item + ['inputs' => []];
                break;
            }
        }

        if (count($func_abi) === 0) {
            throw new TronException("Function $function not defined in ABI");
        }

        if (!is_array($params)) {
            throw new TronException("Function params must be an array");
        }

        if (count($func_abi['inputs']) !== count($params)) {
            throw new TronException("Count of params and abi inputs must be identical");
        }


        $inputs = array_map(fn(array $item) => $item['type'],$func_abi['inputs']);
        $signature = $func_abi['name'].'(';
        if (count($inputs) > 0) {
            $signature .= implode(',',$inputs);
        }
        $signature .= ')';

        $eth_abi = new Ethabi([
            'address' => new Address,
            'bool' => new Boolean,
            'bytes' => new Bytes,
            'dynamicBytes' => new DynamicBytes,
            'int' => new Integer,
            'string' => new Str,
            'uint' => new Uinteger,
        ]);
        $parameters = substr($eth_abi->encodeParameters($func_abi, $params),2);

        $result = $this->tron->getManager()->request('wallet/triggerconstantcontract', [
            'contract_address' => $contract,
            'function_selector' => $signature,
            'parameter' => $parameters,
            'owner_address' =>  $address,
        ]);

        if(!isset($result['result'])){
            throw new TronException('No result field in response. Raw response:'.print_r($result,true));
        }
        if(isset($result['result']['result'])) {
            if(count($func_abi['outputs']) >= 0 && isset($result['constant_result'])) {
                return $eth_abi->decodeParameters($func_abi, $result['constant_result'][0]);
            }
            return $result['transaction'];
        }
        $message = isset($result['result']['message']) ?
            $this->tron->hexString2Utf8($result['result']['message']) : '';

        throw new TronException('Failed to execute. Error:'.$message);
    }
}
