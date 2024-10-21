#!/bin/bash
# Script para a conversão de um arquivo binário em base 64 e o split para a inserção em um CLOB
# maicon.zucco
FILE="$1"
VARIABLE="$2"
DESTINYFILE="$3"
 
[ $# -ne 3 ] && { echo "Uso: $0 arquivoOrigem nomeDaVariavel arquivoDestino "; exit 1; } # Make sure file exits else die
[ ! -f $FILE ] && { echo "$0: arquivo $FILE não encontrado."; exit 2; } # the while loop, read one char at a time
 
# Convert for BASE64
# Check if base64 supports the -w option
if base64 -w 0 /dev/null >/dev/null 2>&1; then
  BASE64FILE=$(base64 -w 0 $FILE)

# Check if base64 supports the -i option
elif base64 -i $FILE >/dev/null 2>&1; then
  BASE64FILE=$(base64 -i "$FILE" | tr -d '\n')

else
  # Use base64 and remove newlines with tr
  BASE64FILE=$(base64 "$FILE" | tr -d '\n')
fi
 
> $DESTINYFILE
 
arrayFile=$(echo $BASE64FILE | fold -w3999) # split base64 with max VARCHAR2 length
 
# Print the split string
COUNTER=0
for i in $arrayFile; do
  ((COUNTER++))
  if [ $COUNTER -eq 1 ]; then
    echo "$VARIABLE := to_clob('$i');" >> "$DESTINYFILE"
  else
    echo "$VARIABLE := $VARIABLE || to_clob('$i');" >> "$DESTINYFILE"
  fi
done
 
echo "Conversão concluída. Processadas $COUNTER linhas"