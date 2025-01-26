<?php
	class CustomFunction3{
		//Do not change the class name or the name of the static function.
		public static function entryPointCustomFunction3($entityData)
		{
			global $A_URL;
			
			$url = $A_URL[2].'/'.$A_URL[3].'/'.$A_URL[4];
			
			$array = $entityData->getData();
		
			try {
			
					$curl_post_data = array(
						'name' 		=> $array['accountname'],
						'nit' 		=> $array['siccode'],
						'company'	=> $array['id'],
						'crm'		=> $A_URL[4],
						'url'		=> $url
					);
					
					
					$url = 'https://keaser.rhiss.net/upddate-company-kaeser';

					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($curl_post_data));
					curl_exec($curl);
					curl_close($curl);
					
					return true;
			
			
			} catch (\Exception $e) {
				
			
				return true;
			
			}
			
			return true;
		}

	}
?>