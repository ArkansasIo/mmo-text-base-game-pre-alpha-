<?php
final class SmtpMailer
{
    private string $host; private int $port; private string $user; private string $pass; private string $from; private string $encryption; private int $timeout;
    public function __construct()
    {
        $this->host=(string)(getenv('SMTP_HOST')?:'');$this->port=(int)(getenv('SMTP_PORT')?:587);$this->user=(string)(getenv('SMTP_USERNAME')?:'');$this->pass=(string)(getenv('SMTP_PASSWORD')?:'');$this->from=(string)(getenv('SMTP_FROM')?:'root@universecivilization.game');$this->encryption=strtolower((string)(getenv('SMTP_ENCRYPTION')?:'tls'));$this->timeout=max(5,(int)(getenv('SMTP_TIMEOUT')?:15));
    }
    public function configured(): bool { return $this->host!==''&&filter_var($this->from,FILTER_VALIDATE_EMAIL)&&$this->user!==''&&$this->pass!==''; }
    public function send(string $to,string $subject,string $body): array
    {
        if(!$this->configured())return ['ok'=>false,'response'=>'SMTP settings are incomplete'];if(!filter_var($to,FILTER_VALIDATE_EMAIL))return ['ok'=>false,'response'=>'Recipient address is invalid'];
        $remote=($this->encryption==='ssl'?'ssl://':'').$this->host.':'.$this->port;$errno=0;$err='';$fp=@stream_socket_client($remote,$errno,$err,$this->timeout,STREAM_CLIENT_CONNECT);if(!$fp)return ['ok'=>false,'response'=>'SMTP connection failed'];stream_set_timeout($fp,$this->timeout);
        $this->expect($fp,220);$this->command($fp,'EHLO universecivilization.game',250);if($this->encryption==='tls'){$this->command($fp,'STARTTLS',220);if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))return $this->fail($fp,'TLS negotiation failed');$this->command($fp,'EHLO universecivilization.game',250);}$this->command($fp,'AUTH LOGIN',334);$this->command($fp,base64_encode($this->user),334);$this->command($fp,base64_encode($this->pass),235);$this->command($fp,'MAIL FROM:<'.$this->from.'>',250);$this->command($fp,'RCPT TO:<'.$to.'>',250);$this->command($fp,'DATA',354);$headers='From: '.$this->from."\r\n".'To: '.$to."\r\n".'Subject: '.str_replace(["\r","\n"],'',$subject)."\r\n".'MIME-Version: 1.0' ."\r\n".'Content-Type: text/plain; charset=UTF-8' ."\r\n\r\n";$payload=str_replace(["\r\n","\r"],"\n",$headers.$body);$payload=preg_replace('/^\./m','..',$payload);fwrite($fp,$payload."\r\n.\r\n");$this->expect($fp,250);fwrite($fp,"QUIT\r\n");fclose($fp);return ['ok'=>true,'response'=>'SMTP accepted message'];
    }
    private function command($fp,string $command,int $code): void { fwrite($fp,$command."\r\n");$this->expect($fp,$code); }
    private function expect($fp,int $code): void {$line='';while(($part=fgets($fp,515))!==false){$line.=$part;if(isset($part[3])&&$part[3]===' ')break;}if((int)substr($line,0,3)!==$code)throw new RuntimeException('SMTP response mismatch');}
    private function fail($fp,string $response): array {fclose($fp);return ['ok'=>false,'response'=>$response];}
}
?>
