# IPP-projekt - parser v jazyce PHP
## Celkový popis
Program nejdříve načte všechny řádky ze standardního vstupu a každý řádek po jednom přemění na
“tokeny”. Nad nimi pak postupně udělá syntaktickou analýzu a přemění je na XML formát.
## Třída Token
Objekty třídy Token jsou pomocí “konstruktoru” vytvořeny s proměnnými type a value.
Proměnná type obsahuje hodnotu výčtového typu TokenType, který popisuje všechny typy,
kterými může “token” nabývat, např. INSTR, VAR, LABEL, atp. Proměnná value pak ukládá
hodnotu “tokenu”, určenou vstupním kódem. “Token” dále může obsahovat proměnné data_type
a stack_type. Ty, podobně jako type obsahují hodnoty výčtových typů DataType a
StackType.
## Třída Tokenizer
Tokenizer se stará o rozdělení celých řádků vstupního kódu na “tokeny”. Nejdříve pomocí funkce
Line2UTokens řádek po řádku “naseká” na “neověřené tokeny” podle popisu v dokumentaci, tj.
podle mezer a podobně. Funkce VerifyTokens se potom postará o ověření, zda jsou “tokeny”
podle dokumentace validní, a doplní data do objektů “tokenu”.
## Třída Parser
Tato třída má za úkol brát pole “tokenů”, zkontrolovat syntaxi a parsovat ji do XML. Funkce
ParseTokens používá několik proměnných pro kontrolu “tokenů”, jako například expect1,
expect2, expect3 a expect4. Tyto proměnné určují očekávaný typ “tokenu” v daném
okamžiku a v závislosti na tom, jaký “token” se vyskytne, se nastaví nové očekávané “tokeny”.
Funkce pracuje pomocí cyklu “foreach”, který projde všechny “tokeny” v poli tkns. Pro každý
“token” se poté volá “switch” blok, který určuje akce na základě typu “tokenu”. Například pokud se
zpracuje “token” typu TokenType::INSTR, jedná se o instrukci a funkce vytvoří nový “element”
v XML dokumentu a nastaví očekávané “tokeny” pro argumenty této instrukce.
Funkce obsahuje blok “case” pro každou podporovanou instrukci. V každém takovém bloku jsou
nastaveny očekávané “tokeny” pro argumenty instrukce, což umožňuje funkci zkontrolovat správnost
syntaxe instrukce.
Dále funkce zpracovává “tokeny” typu TokenType::VAR a TokenType::SYMB, což jsou
proměnné a symboly, které se používají jako argumenty instrukcí. Funkce je transformuje do XML
“elementů” a přidává je jako potomky XML “elementu” instrukce.
Podobně zpracovává zbylé typy “tokenů”.
