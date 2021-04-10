<?php
/*
 * MoveMail 1.1 (09/04/2020)
 * Migração de E-mails de uma Hospedagem para Outra
 * Silvio Tenfen Junior <silvio@tenfen.com.br>
 */

class Movemail {

    public function init($argv) {
        require_once('movemail.conf.php');

        $comando = isset($argv[1]) ? $argv[1] : '';
        $opcao_1 = isset($argv[2]) ? $argv[2] : '';
        $opcao_2 = isset($argv[3]) ? $argv[3] : '';
        $opcao_3 = isset($argv[4]) ? $argv[4] : '';

        if ($comando == 'listar_origem') {
            $this->listar($this->origem_host, $this->origem_user, $this->origem_pass);

        } elseif ($comando == 'listar_destino') {
            $this->listar($this->destino_host, $this->destino_user, $this->destino_pass);

        } elseif ($comando == 'deletar_pasta_destino') {
            $this->deletar_pasta($this->destino_host, $this->destino_user, $this->destino_pass, $opcao_1);

        } elseif ($comando == 'copiar') {
            $this->copiar($opcao_1, $opcao_2, $opcao_3);

        } elseif ($comando == 'migrar') {
            $this->migrar($opcao_1);

        } else {
            echo "Opção inválida.\n";
        }
    }

    public function listar($host2, $user, $pass, $return = 'echo') {

        $stream = imap_open("{".$host2."}", $user, $pass);
        //echo $host2.' - '.$user.' - '.$pass; exit;

        if ($stream) {
            $pastas = array();
            $list = imap_list($stream, "{".$host2."}", "*");

            if (is_array($list)) {
                foreach ($list as $pasta) {
                    $pos = strpos($pasta,"}");
                    $pasta = substr($pasta,$pos+1);

                    if ($return == 'return') {
                        $pastas[] = $pasta;
                    } else {
                        echo $pasta."\n";
                    }
                }
            }

            if ($return == 'return') {
                return $pastas;
            }
        } else {
            echo 'Não foi possível conectar no e-mail!';
        }

        imap_close($stream);
    }

    public function deletar_pasta($host2, $user, $pass, $dir) {

        $stream = imap_open("{".$host2."}", $user, $pass);

        if ($stream) {
            imap_deletemailbox($stream,"{".$host2."}".$dir);

        } else {
            echo 'Não foi possível remover a pasta no e-mail!';
        }

        imap_close($stream);
    }

    public function copiar($pasta_origem, $pasta_destino = '', $apartir = 0) {
        // Se pasta_destino estiver vazio consideramos o mesmo que pasta_origem
        if ($pasta_destino == '') {
            $pasta_destino = $pasta_origem;
        }

        $sourcestream = imap_open("{".$this->origem_host."}", $this->origem_user, $this->origem_pass);
        $deststream = imap_open("{".$this->destino_host."}", $this->destino_user, $this->destino_pass);

        if ($sourcestream && $deststream) {

            $list = imap_list($sourcestream, "{".$this->origem_host."}", "*");
            $destlist = imap_list($deststream, "{".$this->destino_host."}", "*");

            if (is_array($list)) {
                
                echo "➡️ ".date('d/m/Y H:i:s').': '.$pasta_origem.' para '.$pasta_destino."... ";
                imap_reopen($sourcestream ,"{".$this->origem_host."}".$pasta_origem);

                if ($sourcestream) {

                    // if (!array_search("{".$this->destino_host."}".$pasta_destino,$destlist)) {

                    //     echo "📥 ".date('d/m/Y H:i:s').": Criando caixa de e-mail $pasta_destino em {$this->destino_host}: ";
                    //     if (imap_createmailbox($deststream, imap_utf7_encode("{".$this->destino_host."}".$pasta_destino))) {
                    //         echo "OK\n";
                        
                    //     } else {
                    //         echo "ERRO: " . implode("<br />\n", imap_errors()) . "<br />\n";

                    //         imap_close($sourcestream);
                    //         imap_close($deststream);
                    //         exit;
                    //     }
                    // }

                    imap_reopen($deststream ,"{".$this->destino_host."}".$pasta_destino);

                    if ($deststream)  {             
                        $headers = imap_headers($sourcestream);
                        $total = count($headers);
                        echo "$total mensagens.\n";

                        $mailbox_nmsg = 'mailbox/'.$pasta_destino.'.nmsg';
                        $apartir = 0;
                        if ( is_file($mailbox_nmsg) ) {
                            $apartir = readfile($mailbox_nmsg);
                        }

                        $n = 0;
                        if ($headers) {
                            foreach ($headers as $key => $thisHeader) {
                                $n = $key+1;
                                echo '📧 '.date('d/m/Y H:i:s')." | $pasta_destino | $n/$total | ";

                                if ($apartir > $n) {
                                    echo "Pulando rapidamente...\n";
                                    continue;
                                }
                                
                                $header = imap_headerinfo($sourcestream, $n);
                                $messageHeader = imap_fetchheader($sourcestream, $n);

                                $data = date('d/m/Y H:i:s', strtotime($header->date) );
                                echo "$data | $header->subject | ";

                                if ($messageHeader == '') {
                                    echo "E-mail vazio! Parando por aqui.\n";
                                    exit;

                                } else {
                                    $body = imap_body($sourcestream, $n);

                                    if (imap_append($deststream,"{".$this->destino_host."}".$pasta_destino,$messageHeader."\r\n".$body)) {
                                        if ( !imap_setflag_full($deststream,$n,'\\SEEN') ) {
                                            echo "Não é possível setar a flag \\SEEN";
                                        }
                                        
                                        $this->gravar_dados($n, $mailbox_nmsg);
                                        echo "OK\n";

                                    } else {
                                        echo "ERRO\n";
                                        exit;
                                    }
                                }
                                $n++;
                            }
                        }
                    }

                }
            }
        }
        imap_close($sourcestream);
        imap_close($deststream);
    }

    // Funcao para gravar dados no disco
	public function gravar_dados($conteudo, $nmArquivo = "DADOS.txt") {
		$local_arquivo = $nmArquivo;
		$fd = fopen($local_arquivo, "a");
		fwrite($fd, $conteudo);
		fclose($fd);
	}

    public function migrar($apartir = 0) {
        echo "😎 ".date('d/m/Y H:i:s').": Iniciando Migração de Mensagens de E-mail...\n";

        // Lista pastas e migrando mensagens
        $pastas_origem = $this->listar($this->origem_host, $this->origem_user, $this->origem_pass, 'return');

        foreach ($pastas_origem as $pasta_origem) {
            // Se a pasta estiver na lista de pastas ignoradas entao pulamos
            if ( in_array(trim($pasta_origem), $this->pastas_ignorar) ) {
                continue;
            }

            $this->copiar($pasta_origem, '', $apartir);
        }

        echo "🏆 ".date('d/m/Y H:i:s').": Migração Concluída!\n";
    }
}

$obj = new Movemail();
$obj->init($argv);