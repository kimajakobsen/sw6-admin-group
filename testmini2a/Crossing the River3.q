//This file was generated from (Academic) UPPAAL 4.1.9 (rev. 5027), March 2012

/*

*/
Pr[ <= 300](<> right[0] == BOY and right[1] == NOT_A_PERSON)

/*

*/
A[] !deadlock 

/*

*/
E<> Boy1.L == 1 && Boy2.L == 1 && Boy3.L == 1 && Boy4.L == 1 && Girl1.L == 1 && Girl2.L == 1  && Girl3.L == 1 && Girl4.L == 1 && Dad.L == 1 && Mom.L == 1 && Police1.L == 1 && Police2.L == 1 && Thief1.L == 1 && Thief2.L == 1

/*

*/
E<> Boy1.L == 1 && Boy2.L == 1 && Boy3.L == 1 && Boy4.L == 1 && Girl1.L == 1 && Girl2.L == 1  && Girl3.L == 1 && Girl4.L == 1 && Dad.L == 1 && Mom.L == 1 && Police.L == 1 && Thief.L == 1

/*

*/
E<> Boy1.L == 1 && Boy2.L == 1 && Boy3.L == 1 && Girl1.L == 1 && Girl2.L == 1 && Girl3.L == 1 && Dad.L == 1 && Mom.L == 1 && Police1.L == 1 && Thief1.L == 1

/*

*/
E<> Boy1.L == 1 && Boy2.L == 1 && Girl1.L == 1 && Girl2.L == 1 && Dad.L == 1 && Mom.L == 1 && Police1.L == 1 && Thief1.L == 1
