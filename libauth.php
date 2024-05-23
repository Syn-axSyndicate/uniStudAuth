<?php
//Imports

//functions
class Auth_Check{
	private $headers;
	private $payload;
	private function setup($xpath,$cookie){
		$this->headers=array(
            'Host'=>'gbpuat.auams.in',
            'User-Agent'=>'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/115.0',
            'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'=>'en-US,en;q=0.5',
            'Accept-Encoding'=>'gzip, deflate, br',
            'Referer'=>'https://gbpuat.auams.in/',
            'Content-Type'=>'application/x-www-form-urlencoded',
            'Content-Length'=>'1156',
            'Origin'=>'https://gbpuat.auams.in',
            'DNT'=>'1',
            'Connection'=>'keep-alive',
            'Cookie'=>$cookie,//need to work here
            'Upgrade-Insecure-Requests'=>'1',
            'Sec-Fetch-Dest'=>'document',
            'Sec-Fetch-Mode'=>'navigate',
            'Sec-Fetch-Site'=>'same-origin',
            'Sec-Fetch-User'=>'?1',
            'TE'=>'trailers',
        );
        $this->headers = json_encode($this->headers, JSON_UNESCAPED_SLASHES);
        $this->payload=array(
        	'__EVENTTARGET'=>'',
            '__EVENTARGUMENT'=>'', 
            '__VIEWSTATE'=>$xpath->query("//*[@id='__VIEWSTATE']")->item(0)->getAttribute("value"),
            '__VIEWSTATEGENERATOR'=>$xpath->query("//*[@id='__VIEWSTATEGENERATOR']")->item(0)->getAttribute("value"),
            '__EVENTVALIDATION'=>$xpath->query("//*[@id='__EVENTVALIDATION']")->item(0)->getAttribute("value"),
            "ctl00\$ContentPlaceHolder1\$txtUserId"=>'<user:>',
            "ctl00\$ContentPlaceHolder1\$txtPwd"=>'<password:>',
            "ctl00\$ContentPlaceHolder1\$txt_capt"=>'<captcha_text:>',
            "ctl00\$ContentPlaceHolder1\$btnLogin"=>"Login"
        );
        $this->payload = json_encode($this->payload, JSON_UNESCAPED_SLASHES);
	}

	public function Captcha(){
		$endpoint='https://gbpuat.auams.in/';
		//equivalent to data=_session.get(url)
        $c_url = curl_init($endpoint."Login.aspx");
        // Set cURL options
        curl_setopt($c_url, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c_url, CURLOPT_HEADER, true); // Include the header in the output
        curl_setopt($c_url, CURLOPT_NOBODY, false); // Include the body in the output
        // Execute the cURL session
        $response = curl_exec($c_url);
        // Check for cURL errors
        if(curl_errno($c_url)) {
            echo 'Curl error: ' . curl_error($c_url);
            exit;
        }
        // Get the size of the header
        $header_size = curl_getinfo($c_url, CURLINFO_HEADER_SIZE);
        // Extract the header
        $header = substr($response, 0, $header_size);
        // Extract the body
        $data = substr($response, $header_size);
        // Close the cURL session
        curl_close($c_url);
        // Find and extract the cookie value
        preg_match('/^set-cookie:\s*(.*?);/im', $header, $cookie);
        $xcookieValue = isset($cookie[1]) ? $cookie[1] : '';

		if ($data===False){
			echo 'Failure to reach the endpoint \n';
			return '404';
		}
		else{
			libxml_use_internal_errors(true);
			$dom=new DOMDocument();
			$dom->loadHTML($data);
			$xpath = new DOMXPath($dom);
			$div = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' col-md-8 ')]")->item(0);
            if ($div) {
                // Create a new DOMXPath object to search within the specific div
                $divXpath = new DOMXPath($dom);
                $img= $divXpath->query(".//img", $div)->item(0)->getAttribute('src');
                echo "$xcookieValue \n";
                $this->setup($xpath,$xcookieValue);
                $this->LoadImg($endpoint.$img);
                return $endpoint.$img;
            } else {
                echo "Div with class 'col-md-8' not found";
                return 'NULL';
            }
            
            libxml_clear_errors();
		}
	}

	function Login($username,$password,$captcha_text){
            echo "username : $username,\npassword : $password\ncaptch : $captcha_text\n";
            $endpoint='https://gbpuat.auams.in/';
            //equivalent to data=_session.get(url)
            $c_url = curl_init($endpoint."Login.aspx");
    	}
    function LoadImg($url){
        $session = curl_init();
        // Set CURL options for the first request
        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    
        // Execute the first POST request
        $response = curl_exec($session);
        file_put_contents('captcha.jpeg', $response);
        curl_close($session);
    }
    function handshake($username,$password,$captcha_text) {
        $this->payload=str_replace('<user:>', $username, $this->payload);
        $this->payload=str_replace('<password:>', $password, $this->payload);
        $this->payload=str_replace('<captcha_text:>', $captcha_text, $this->payload);
        $session = curl_init();
        echo $this->payload."\n";
        $endpoint = 'https://gbpuat.auams.in/Login.aspx';
    
        // Set CURL options for the first request
        curl_setopt($session, CURLOPT_URL, $endpoint);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $this->payload);
        curl_setopt($session, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    
        // Execute the first POST request
        $response = curl_exec($session);
        if ($response === false) {
            throw new Exception("CURL Error: " . curl_error($session));
        }
         // Log the response for debugging
        file_put_contents('login_response.html', $response);

        // Set CURL options for the second request
        $endpoint = 'https://gbpuat.auams.in/Student/basicstudent.aspx';
        curl_setopt($session, CURLOPT_URL, $endpoint);
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_POSTFIELDS, $this->payload);
        curl_setopt($session, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    
        // Execute the second POST request
        $response = curl_exec($session);
        if ($response === false) {
            throw new Exception("CURL Error: " . curl_error($session));
        }
    
        // Close the CURL session
        curl_close($session);
        // Log the response for debugging
        file_put_contents('student_response.html', $response);

        // Load the response into DOMDocument for parsing
        $dom = new DOMDocument();
        @$dom->loadHTML($response); // Suppress warnings from invalid HTML
    
        // Use DOMXPath to find elements by ID
        $xpath = new DOMXPath($dom);
        $nameNode = $xpath->query("//span[@id='ContentPlaceHolder1_lblName']")->item(0);
        $disciplineNode = $xpath->query("//span[@id='ContentPlaceHolder1_lbldicipline']")->item(0);
    
        if ($nameNode) {
            $name = trim($nameNode->nodeValue);
        } else {
            throw new Exception("Wrong Credentials...");
        }
    
        if ($disciplineNode) {
            $discipline = trim($disciplineNode->nodeValue);
        }
    
        echo "checking user trials sessions...\n";
        return sprintf("%s(%s)", $name, $discipline);
    }

}

$main=new Auth_Check();
echo $main->Captcha()."\n";
$captcha=readline('Enter a Captch : '); 
$test_credentials=$main->handshake("59372","India@12345",$captcha);
echo $test_credentials;
?>


//possible error would be that opening the captcha externally might change its token to autheticate