public function  place($anzhiold){


   //        unset($_SESSION['boss'] );
		 // unset($_SESSION['anzhiold'] );exit;
		//查询一级的总数
		$age_count=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid  ORDER by ruhui_time ASC,mem_num asc", array(':agentid' =>$anzhiold));

		//判断一级是否满2人
		if($age_count>=2){

			//查询一级下面的人数
			$two_agen=pdo_fetchall("SELECT id,ruhui_time FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid  ORDER by ruhui_time ASC" , array(':agentid' =>$anzhiold));


			$anzhi=$two_agen[$this->num];


			$three=pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename('ewei_shop_member')." WHERE agentid = :agentid  ORDER by ruhui_time ASC" , array(':agentid' =>$anzhi['id']));


			if($three<2){

				return $anzhi['id'];

			}else{

                    $two_agen =  $this->datefor($two_agen);
                    $_SESSION['anzhiold'][]  = $two_agen[$this->num];

				    $this->num = $this->num + 1 ;


					$anzhi=$two_agen[$this->num]['id'];


					if (empty($anzhi)) {
						if ($_SESSION['anzhi2']== 1) {
					    $this->num = 0 ;

                        $_SESSION['boss'] = $_SESSION['anzhiold'];
                        $_SESSION['up'] = $_SESSION['boss'];
                        unset($_SESSION['anzhiold']);
					    return $this->place($_SESSION['boss'][0]['id']);
						}else{

							foreach ($_SESSION['up'] as $key => $value) {
								if ($value['id'] == $_SESSION['anzhi2']) {

									if (!empty($_SESSION['up'][$key+1]['id'])) {
									$anzhiold2 = 	 $_SESSION['up'][$key+1]['id'];

									$this->num = 0 ;
							        return $this->place($anzhiold2);
									}else{
										foreach ($_SESSION['up'] as $key => $value) {
											 if($value['id'] == $_SESSION['anzhi2']){
                                                 if(empty($_SESSION['up'][$key+1]['id'])){
                                                 	$this->num = 0 ;
                                                    $_SESSION['up'] = $_SESSION['anzhiold'];
                                                    // halt($_SESSION['anzhiold'][0]['id']);
                                                 	return $this->place($_SESSION['anzhiold'][0]['id']);
                                                 }else{
                                                 	 $this->num = 0 ;
                                                 	return $this->place($_SESSION['up'][$key+1]['id']);
                                                 }

											 }
										}



									}

								}

							}

						}



					}else{





					  $_SESSION['anzhi'] = $this->datefor($two_agen);


					  $_SESSION['anzhi2'] = $anzhiold;

					  return $this->place($anzhiold);
					}

				}


		}
		else{

			return $anzhiold;
		}


	}

}
