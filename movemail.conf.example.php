<?php

$this->email = 'email@email.com.br';
$this->senha = 'senha';

// Dados do email e host de ORIGEM
$this->origem_host = '192.168.1.10:993/ssl/novalidate-cert';
$this->origem_user = $this->email;
$this->origem_pass = $this->senha;

// Dados do email e host de DESTINO
$this->destino_host = 'mail.dominio.com.br:143/novalidate-cert';
$this->destino_user = $this->email;
$this->destino_pass = $this->senha;

// Pasta para ignorar - Os exemplos abaixo realmente não compensa copiar
$this->pastas_ignorar = array(
    'INBOX.spam', // Spam
    'INBOX.Trash', // Lixeira
    'INBOX.Drafts', // Rascunhos
    'INBOX.Junk' // Spam
);