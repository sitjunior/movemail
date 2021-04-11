#!/bin/sh
# Esse simples script serve para quando ocorrer erros como falta de memória ou kill
# você consiga executar o comando novamente de maneira automática
#
# Como usar?
# ==========
#
# 1. Altere o comando persistente que você deseja usar
# 2. Depois vá na linha de comando e execute para rodar em background:
# "sh persistente.sh >> persistente.log &"
# 3. Use "tail -f persistente.log" para visualizar o log conteúdo

while true; do
    # Coloque aqui abaixo o comando que você gostaria de persistir
    php7.2 movemail.php copiar INBOX.Sent
    sleep 1
done