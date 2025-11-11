-- Database Backup
-- Generated on: 2025-11-10 13:20:25

SET FOREIGN_KEY_CHECKS=0;

-- Table: attendance
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int NOT NULL,
  `status` enum('Present','Late','Absent') COLLATE utf8mb4_unicode_ci DEFAULT 'Present',
  `time_in` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date` date NOT NULL,
  `session_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_date` (`date`),
  KEY `idx_student` (`student_name`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: certificates
DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_date` date DEFAULT (curdate()),
  `certificate_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_email`),
  KEY `idx_program` (`program_id`),
  KEY `idx_date` (`issue_date`),
  CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: coordinator_images
DROP TABLE IF EXISTS `coordinator_images`;
CREATE TABLE `coordinator_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coordinator_id` int NOT NULL,
  `image_data` mediumblob NOT NULL,
  `image_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` int NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coordinator_id` (`coordinator_id`),
  CONSTRAINT `coordinator_images_ibfk_1` FOREIGN KEY (`coordinator_id`) REFERENCES `coordinators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coordinator_images` (`id`, `coordinator_id`, `image_data`, `image_type`, `file_size`, `uploaded_at`) VALUES ('1', '2', 'ÿØÿà\0JFIF\0\0`\0`\0\0ÿþ\0>CREATOR: gd-jpeg v1.0 (using IJG JPEG v62), default quality\nÿÛ\0C\0		\n\r\Z\Z $.\' \",#(7),01444\'9=82<.342ÿÛ\0C			\r\r2!!22222222222222222222222222222222222222222222222222ÿÀ\0\0È\0È\"\0ÿÄ\0\0\0\0\0\0\0\0\0\0\0	\nÿÄ\0µ\0\0\0}\0!1AQa\"q2‘¡#B±ÁRÑð$3br‚	\n\Z%&\'()*456789:CDEFGHIJSTUVWXYZcdefghijstuvwxyzƒ„…†‡ˆ‰Š’“”•–—˜™š¢£¤¥¦§¨©ª²³´µ¶·¸¹ºÂÃÄÅÆÇÈÉÊÒÓÔÕÖ×ØÙÚáâãäåæçèéêñòóôõö÷øùúÿÄ\0\0\0\0\0\0\0\0	\nÿÄ\0µ\0\0w\0!1AQaq\"2B‘¡±Á	#3RðbrÑ\n$4á%ñ\Z&\'()*56789:CDEFGHIJSTUVWXYZcdefghijstuvwxyz‚ƒ„…†‡ˆ‰Š’“”•–—˜™š¢£¤¥¦§¨©ª²³´µ¶·¸¹ºÂÃÄÅÆÇÈÉÊÒÓÔÕÖ×ØÙÚâãäåæçèéêòóôõö÷øùúÿÚ\0\0\0?\0óïýæüéÙï7çH‹š°±ñZ™•Éï7ç@/ýæüêÐ‡4y4X.@ÿ\0y¿:zïþó~u Ž¬E¢Â\"EsüMùÓÈqüMùÕ¡¨¦l?÷›ó¨ä.?‰¿:¶TU[™aI–E_© Ã·÷çJdl}æüë6M^Ùs³sûUßZoà„vj›¢¹Y¬KŸâoÎ“/ýæüëûjç8Ä:Q¬\\“ÌQcØš\\È|¬ØÞãø›ó§¬ýæüë)ua‘¾ÜÜ«f¬Ãm1\\+u¸4ÓBåf†æÇÞoÎ£foï7çB¶GN)N=P†o|ýæüéÛßûíùÒŽÔ™¤·7÷›ó¨ÝŸûÍùÓ³M~h;ãoÎ£.ÿ\0ßoÎžâ£Å\"€»ÿ\0}¿:)¦Š\0¹«qŠ¨¼\Z°¯MZ@)Yxªë-IæäS¥pjEmµlÓÂ©bp£’h×›ÅSºÔâƒ#;œ\nõ¬»›ù\'%b%\"Xu?áYNrQãPçØµåûÍfâLª?–?º½Ylîä³’IîNM7§\'“@OO˜þ•\Z³Ed4õ\nH÷¥‘ì}h³tV4l1ÞY ëÓ4ñ¹G!ñ§Ee$œôÂ¬®7UÛÜŠ–íÔj-ô*1Ì£ðc\0ÃÜU¿ì­AþìD¥E&›zƒcÔRæCp—a`½XŽ#g_nß•j[êÊ0äö‡Oþµs’	b”‰#Î*X¤LûžÕQ‘(êN1M8¬ý>á\Z6ÏËÓéVËÖ©ÜÉ«¤cLN4ÄFÔÂ*r¹¨Êâ€!4R¸æŠC/¥©ü¼ŠiŽ¨‘€RNØE\0i\0Y×Ò¦êpƒïŸSéZ¸†sØqYq‹–?¼?1>Ô¤úÔ£zÂ\0zUHíØ¨b=MJqspÒ¹4û£Þ§—eÚ6¨õ¨±¥ìC£7ÌË„íš›Ë]¸éô©uŒ|ÙÏLUÝ#H»Ö§U‚2#îÇ ¡ÉE1svF|v3€Šy5Ñéž–f\r\"¾õÙé^‚Æ5%wÈ:±­èl}€«’uï¢;éabµ‘ËÚø~8\0Çµ_\Zj*ã`ÏÒºmŽÜSÖØu\"¹ÛlëQŠØç…‚ŽÊ¡›NFB<µÏÒº“l¤pµ¶ íëMCÎ5\rÇ<,Á@zóë«´ºhØ÷ë^ëyl6y¯Š4Í¬ÒmÇ5ÑNNög\"’µÑÍÚ3Ã(}Ùñé[›2¸È®~7Ú6‚{÷«ú}ÄŽÅU²WøOzê‹<éD¼PŠTàÕ†p£ØEhd9Fi²-=Ðâ€)°¢¤QHf¬c\"¦Ô1V‚*ˆ\"h½¨àgh(\"šãŠ`cê$$‰À&¹ùn	Sž7ÿ\0/J××Ÿe¢)ãsâ¹†v‘?JÆnÌÚy¹ã°¥ó‰#31Q\"¼Ž\"‰K3W¡øWÁÉŽîñÊy\nG\0Vr©Ë¹½:N£²3<;áMÒêü2CÙS^±¤éöÖQ,pÄ¨ v1ÂŽ•¯l€&1ŠäœÜÙèÓ§+bHÉšV‰j‘a8âšÐ¾;ÔYšì:ÐE?aÏ<R´MéÇ­ÒT•¾x©Ì-Ž†¡’&PIúÓåbºE”VŽkñ‘šÝð¼Šë$¹ŒIµA<c5VæÝ$àŒƒM6µ&^ò±á·1˜ålŽžÕ6†j#$† þ5³â?ìº³ _•†EQÐm—íHz¨ãñ®è;Øòj®VÑ½°Q4u`\nxˆmÍns\05â¬2ä*¼¨ÀÐiæ\\\n)ÒE©Õj%â¤ÅQ$ÀâœŠ‡vhÜq@÷‰Ã2[¢X±8Ú¹ÇM¡PZô½;N¶ÔnX\\–Q¶•ë“ÇòÍSñ?RÆÑoì™Þ4ÆôaÐz×<ä”¬Îºte(s#Áö+&´¡Ó;Ww=«Ö¢Qy àÕÂü>²ó®®nq€%zLp‚yW%V¹Žê\nÐº1V\rZöážÝGÑsüëY[²€8‰.Jÿ\08&™­Á¥@Ì\"i¤kŸ×|eâ-:+y,áŠxÙÔíÝ·>¦ª\r=‡5mN†?^BÚôiÐgUÝü«bÇY·½ÀH„ô+–Ò5\rTÐ›R–{|+ãn6†ŒýkJßSicVtPààãÖœÝ·gSå¡9Ç^ææ+PK¡l\0Ë{Å––Áª÷7jýïJË™\Zr³\Zë]Õç•“OÒ¤ ~6ÞÏÄwã7Â8S¸9©Äš–©q\"ZÊ±G\Z“‘ŽHí\\E¿‹|NÚªÚ$ë¿p>w’1ùs[E»OG©ÛOákwŒ—•·ú©èj4´–ÑR9”ºO\\S_Ô,u_°j)ë–hº­iù«p›—”Ú4Šg—øþ&ŽþÕ±Ã)…£FM!à3`\ní¾\"ÚùšT*2b—†³¿±£³ðäQ„®žnâzÖô¦•Ž:Ô¥6íÑ­+ÉòàUBvšzÉ»­v±®ãLœƒÒ‘æ\0b gÈ ¤éEG+Q@Íÿ\0/Z`$µWV9«p€q@‹¤­H¤‰~ZVÀ¦\"î‹±o¶·ñ)ÇÖ½îÞ­ìœmhÉñ^X²”pÊpÀäWy§j‚çÃ,¬@l•9é\\˜ˆê¦z8:¾ë¦s¿-„\Zu×#&_é]‹£Âæ¹ß$pGq\Z?xI÷úWY\0Á5Í;6vSVV2ÎœY·ËzÔŸØpÉÙQY?ºzf·„(ÃÞ™\"l\\•M^§3>˜¥X&%û©ž\0¤[›T(U^plÉÁÈæªðO&›mî=‰aM‘ðÕñ1`ÃŸ­XHÎ)OáJÀS]:&}ëÂyÊäsRC¢À.À_ßž²g~¦®ÅŒc½Z‹9ªæb²2$ÒÕXáGZlv­zâº\0q¸õ¨&„*Ž}\rKWg#âÈ„ž¸R?»Î©k [höpqÿ\0Ëõ«¾,/KXÇY&UÛ­eø™Ë5š°*D =±[Sä‘ÍR|”—k«ÅÅE´Š¼Ê*	\0½È)¾E0¶K)ªnô†G3óE1ù4R¢\0©â\'pÅ@jHÉÕjFÿ\0-9aTã›Ö¬¬ÃM¤WGáIŸ…Y‚ÈÝ®|°aVô]LiZˆ–E&&\\JŠ±æ‹FØyòTM›Áý›­¼q‚\"dØ×S»‡Jæ%Õì/õD.wvÈÅt¶¯ˆ‚×¢Öç¯	FMò³B\0©˜àš¤ƒV£”m÷¨6E\rDù	òŒæ©Åh¢?µÍ2¢îphÝ ÃÀÖMÈg€Û´i,\'“ŒŠ`ÍÛqlÐ©Ü<î#ZæÖ9÷y.¥‡P>µ—iGVGEUà\nŠÎÖ{+ù\'?;¦É/Ø;­ã[KÉƒéZÆ-¸aÎ+:Ù\nÈódcÖ¯ÙÎóÆZHL1=ýêGrMÄ§½A,…ò3RLü*¡ns@š0õhüÝNÐ\0——èl×+âËuù¹L³æ¶u]vREh¼Òz\Zäonä¾ºi¤àžƒÐW]\Zm>fyøš°pä[4¼T6jG\"«4Dk¨óÈÝ²*¹š¹å1J-ñÖ•†Q(h«2¨Z(ÒÂMJ±b§\n1HEQ#BœÐNj`2´†5œRÊ2´Þ†•›Š\0†L	\"õV½JÊáf³Šd9 ×•0É®×ÂÞe£Ú?&3•úW>\"7Î¼NYò÷:è©©¾éÉª‘0Ó­[f1×5ÂY²pÀdÎªK,P)Ë\rÝ:ÑydncuWe$qƒ\\è°žü©åq“Ã‘œÕÝ#®¦úÞ«(Ã¨,wQ;m	=sYKc\"q¼gê›i \0»©\' š®Tjé£¦Q°Ž¸5i_pÀé\\æž—Ñ[4×s°‰F@ÆN+zÖ@Ñ\'¨ÍC1w¸ùËT¦E»pª2jÛ·\\t®gÅZ‘¦´qðd;iÂ<ÒHŠ³å‹g?Ú¯f˜ÿ\0U@æš[šMÜ×¦\r»»“íÈ¨YyéRÆsN‘p8 C1ŠVE\niÅG4˜ÄQ¹84SfùŽM%\Z±¶EHjº\nwÎ*‰%Ú3O˜­‘JAÅ\014ÌäRí4˜Å!Œ5«áÉZ-b •5•Žk¢ðm˜½¹¾»#1ZG€}\\ÿ\0€¥%x²é|jÇ`—*iëVÒ]ÀsXÏ†\'\'èiñI$\'®EyOsÜÑ´¯ƒŠ•âI—ÅR†t:÷«ÑËœt4Ñ[²±±ËÄýjXì  ,=jÀ+Ý¨’UEãšwc\\!zLm¨88J{Í‡“Te¸šN	À¥p4&ºPS“í\\—‹	6ð1=Xæ¶UIaÎ°¼a(ƒL‰Ø	\0úf´¢ýôsâczlåN3J\0¤<Ðs^‰ã“G€jGäUhÏ5c9V\\ƒPHÜU‰…S\Z\0«#h¤psEHÍ! Æ)P5]zÕ¸Xqš¡âŠ—f(—;ã©Å18â«·Z[‹Ûxß2;f±¯µ”T+o’Çøjj-\"MKQ[u1Fs!ëþÍzÃkP<<™ù§.Çõáo+;’I$œæ¾Žøm\n	iñ…ááäù­TUš5†Â1$Cšb£¡ÁŠØ¸ÓÚÊòX±…É)î*7€0¯Q´š=˜»«”U0Ù§8iéÍÍYŽÜc¦ÅMÌå9ŒTfYÙvä*ûV§ÙATMl9¦#/nzŸz\ZC€8«¦qŠš(1ÐRYa\n¸¹ÿ\0[	ôüô†\'â®ª\\F§‘â}ž×/$ë\"Ú§Û5¾\Z7¨ŒqÔÙå:UÚËÄÍócð­\"¼WopÈƒ½1[QëE\"jîÇR:×«(uGŒÑ¯À§oÅPSµ”$Æ}jOµDçÇçYÙŠÅ†pj z\Z.E (ÊœQV¤AE+ÎmAŒÔ-«…<V+¹-Ö™×½kÊ‡cdë·]‚QUå¿¸›!åsë“T;`SóòœzU¡iIïÈüjÙ<Ñ“MïEÀzòkéÏ‡1ÿ\0Å%¥ž¿¹+æDë_Kü.¸Y¼¦c¢)Cõšz1Çs±Ôtá{o1*}Óëí\\³DQŠ0Ã5ÝãŽ+3TÒEÊùÐŒJ#ûÕçW¥Íª;hÖqv‘Ê0ÜU„ˆŽ¢¤²±V+ÁµH§¯Jãhî¸ÁïšŽE*gw€·\nÌ«[›q÷8àSÎzQkm5ôû\"B}[°¡+» r¶¬†/ç¯\n>ñô¨>\"Ûà\rN€TŽƒ¾+³‚Î;8<¨Ç=Y½MrŸŠÇàmUsÉ€×©†¦ µÜó«Ôsz.ƒRïùzÔxÅÐ™Ê\nå[å<zR‡uèÄcLèsKinßQž22Ù±i¬«¸GêMs})ÊH ŽÔ4˜¬v/2:ðEÌEzèpM›ˆ¬R=izSß\0àS0kB…iÊpÜúSxèI¦ŠN¦—ñâ€$\\äW¾|¼/á³	lùRœ:ð%ã“Í{OÀÉ„‘êV¤òŒ®±¦™Höøf*q‚*Œ*T“Ú®#õ„‘¢3õ-5gX€ù×>NÆ*ÊAEvu›©i‹tždXYGZå«Jú£¢•^]Îç=)¬}iÄäŒ€pH9úÕ›6[æÜÙXä÷5Î¡&íc±Î1W¹\rŒº„¡WåŒ}æ5Ô[ZGiŽ´w=Í>(RÖ8Ô*J“p×N’©ÁV«›ò!›¤šòÿ\0‹mÿ\0Ê£ýì)£5é:›¢Ÿ,üÕãÿ\0Žß-ó4ª1žµÛM^70‘âp8¦‘JÄƒHi™‰ŠAÖœzQŠ\0B3@âŽ‚ŒÐE.2p( ±ùŽi3E€3IE€P(ïE\0£­z§ÀÛÁ‹æµ?òÞÜãêÿ\0^Š(\Z>‰1Œt¤\nPQEbG+×žx³Æ——\Z£è*×	ÿ\0]V?UúÑEiF*RÔ™2=-.ôËGCp$’C¹ŽÞ3ì;Uý7Å’é¡b½\"hIÁ}å¢Šê8Ûbc&Ý™ÙÇqÜÏo\"ÉŒ«/B*\'a»ƒEÅÍ$›<ñ^Sñ¦´Ð¬9y®3øÿ\0×¢ŠÙI­	g…=4(¢ƒ1sšZ(¦Gj( rh¢Š@ÿÙ', 'image/jpeg', '5332', '2025-11-09 07:51:48');
INSERT INTO `coordinator_images` (`id`, `coordinator_id`, `image_data`, `image_type`, `file_size`, `uploaded_at`) VALUES ('2', '3', 'ÿØÿà\0JFIF\0\0`\0`\0\0ÿþ\0>CREATOR: gd-jpeg v1.0 (using IJG JPEG v62), default quality\nÿÛ\0C\0		\n\r\Z\Z $.\' \",#(7),01444\'9=82<.342ÿÛ\0C			\r\r2!!22222222222222222222222222222222222222222222222222ÿÀ\0\0È\0È\"\0ÿÄ\0\0\0\0\0\0\0\0\0\0\0	\nÿÄ\0µ\0\0\0}\0!1AQa\"q2‘¡#B±ÁRÑð$3br‚	\n\Z%&\'()*456789:CDEFGHIJSTUVWXYZcdefghijstuvwxyzƒ„…†‡ˆ‰Š’“”•–—˜™š¢£¤¥¦§¨©ª²³´µ¶·¸¹ºÂÃÄÅÆÇÈÉÊÒÓÔÕÖ×ØÙÚáâãäåæçèéêñòóôõö÷øùúÿÄ\0\0\0\0\0\0\0\0	\nÿÄ\0µ\0\0w\0!1AQaq\"2B‘¡±Á	#3RðbrÑ\n$4á%ñ\Z&\'()*56789:CDEFGHIJSTUVWXYZcdefghijstuvwxyz‚ƒ„…†‡ˆ‰Š’“”•–—˜™š¢£¤¥¦§¨©ª²³´µ¶·¸¹ºÂÃÄÅÆÇÈÉÊÒÓÔÕÖ×ØÙÚâãäåæçèéêòóôõö÷øùúÿÚ\0\0\0?\0à@RùtØù«\n¼VÆD;qHW5gÊÍI¢ÀV	R¤y©ub(¨°Ž\ZÆ\0éV`\nB¢˜Šû1QÈ¼TóI1—•ÕPu$Ößˆí”•·Vÿ\0xð(\Z4:\Z<Åñ®VçVº›!Ÿ`=—ŠÏi[9Ü:‡$R‹;‘µº0?k„89W`}[‡X½ƒ˜°›šJH|ŒìJÒ+ÛÄpÈÜ!Fõ^EkG4r xØ2žâ¬’aHÔ©¥¨„RPM&iTliæ˜TÐJŒÔŽ*<Ph ÑHeØÇ5r1T×Š°T‰. 2ŒUuš¤ór)ˆi\\\Z•mD[4ÒiµçqU®ï£µ…¥•‚¨ýi®+YÔ^öñ†q\n(þ´›²\ZWa©êÓj2òJÄ>êV~OJ\'œqR*dô¬›¾æ‹B2Üb˜EY1óÍ\'’HãšW)ñEX6ìÝcøSL,€þT]Ì‡Þ­Z_Mfû£n;©èjá4Â¥zÓNÛ	«ŽŸ¨G}GË 2Õí ×\rgpö·)*ž‡Ÿq]²8xÕÁá†EjÌš°â¢š@¤fâ˜^bši»éAÍ\01ª2*r¹¦2â€ 4R¸æŠC/„§b§òò)¦*¢HÀ§NØEI4\0(4à¹£îŠ’?˜Ð$M°HÞŠOé^kÔ×©Ïm%Ç÷ò¯/Œ|Õ.ÐBÒµ«k¥‡pqL°ˆdw®†Î88®:•\ZvGm*IêÉ¬´^7F	­ëmÉ@ýÂþTûEzsZ¶éœW,ªHî8®…1¢Ù¿ü©G†¬sä(?JÝ¶¶sWV×Ó¹¤7œlþ²d#ÊQî+˜Õ|µK[þíz¤Ö»G³.#9ÕY&D¨A£Áîíd´¢•J°õ®ŸD”Ë¥¦z¡+Z6Óãû8¸UÃ†Á>Õ™á´&ÂQé\'ôéQŸ2¹å×‡#±}³šQEJb9§ª:VÇ9P¦\r*ðjËÅš‹ËÁ§`¸å\\Ó$Z•Ò:Ð\'T’\n)×ˆdTÞ]CÀm5D´^Ôp3Š¶M‘~Z\0Ïu¢.\r9ÁÍ\"ñH[³ƒýÓ^d‘aÖ½#pÚ~•Á¢~ô¯¡¬ê½\ri+²ÍŸË·ë]UZŸZå—	ZVzÌhêœz×Ó–Ç£M¨èÎºÝŽàtjvŽ+›ÒõX2ƒï]]¤±qµÕƒ‹G\\dšÐÑ¶)óŒÓÖà3ð(Û¨ÁÆkBÎÒÍSt³*ŸsMA²e$µfdüô8ÍgÜ/U¿Ó­\\…»‰‡³W;7ˆ,ÉÙ¿ƒßÒ¥ÂCU#mÌ?B²é²3\\ß‡#Ùc7¼§ù\nì58ÖêÊLr1kžÓâZõbk»úv1krÊ &¥1µ\n·50|ŒWiÀG°Q4u`šD6æ€) \0j91VYA$\n­4l¦•$S™xæŠ\0ÔE«µÔ‚P)’L; ­C»4›Ž(9\Z„ŒTÜ“HÉ‘@Ê­&+•ÿ\0¥ËŽÌ[¾¸’[ù9‡h\0úS-y¼÷ë\\•j§¡ÙF‹V‘ZXžBdÔ×C¢èvR…79ë“AµSdPXÒÚøròæ_2Y\\)èJææés­BÎö¹Ô\'‚ô»ˆ‹E(SŽ0Ý*¼ZeÆ‰?ü|4‘ÆM\Z†Å”ìï<ò‚‰Ú­M~f‰c‚Yw¸c“ê;TMé£5§uV:;	^hÁ\0ž*•õœ×ÈgeVóÒ¶4)X¶@ÎÚ£ªC<³Û¾zT]›=w2l¼eq&^á÷g%‰§ê¶Œ¬WIæ²`ÐµÉµ\'’-E¢ˆà•f9CZI¿kr|çÁž2yµm¥¹Ì’æØÍÓíî K‹9‰`ŸpŸJÍòÊ®=+¼û4sCæ…Úøä\Z¦4%°’P§ÌÉç=*èUP“rêEz©£Ðã‚ã­JŒæ¤–=¤Žâ«A¯Dò‹BD¥y>\\\n¦[i§¤›ºÐ%‰0w\ZeÁ¥#Ì\0Å@Ï‘@ÉÒŠŠW¢€4÷ñÖ˜	-UÑŽzÕ¸@8 (¤Š\'­I\r´¯L’//Æâž_5ŒÐ3‘ÔÐÛj7¸~5¬„á½kwY²3À%A—AÈõ…ÀÃV<²=\Z3æŠò;/5Aa]Ý…ªy\0`\nà´K€Š£½wºtždb¹^ç¡Ý~Îª‡jŒ×®L°ß`òÄà\nïoe[.{·¡8¯8†ÒmUŸT”ü¥²ì)4Èì4W+b¨­k5Žv8ëÜU-mœY¤ÆASD¦Ø›ˆ\\4mèsJÖ+tÑ¤¶‰è1MxP©íßÏŒ\Z{©E9{£j`Þv÷¢ÎUþÈ—qèÄ*ME²®}+\"æåmtrªÇÌœž=4¬‡Qªpæg=/Ï#7©& 1äÔÎx¦§5ëž	ZHx¨v‘Z¢«È\0†S|Ôeð*YXb©»ÐS>MÇäÑR3LSÄNáŠ€ûT‘’*„jÆøZsÂ©Ç7­YY†)ˆiR*7&¬3ZN(2I¬]JÍbq<c\0Ÿ˜z\Z×-PÏ¡î8¨œy•)ÍÆW*éÒ™jôm&OÝ¯½yµ‡ÊÀÆ»í\"_‘~•åKF{t¥tuW¦	´‰`˜$]¤Wº|PØ>dÁžH#ò­]Nõã‡¯JÎ[È’0Ò8i]±è´44­%-ìü â~nÝ½*Þ‘£’Þ7e·fÜ¨I;~•VßV€²íò­{mFÚã :yªRŠ½t6ò•ÎGj’òPÑUtºY$¾(¸åi7b»»1o›e¼’9àk‹¹¼{‰·í(ôÕx†Qœê/òŠãBWf:98ê­µ\\’)7mÓÀ¨ðMvxçš«É6{ÔÁª­Í!‘ÈÙX‚M^ò‰ [ã­‰CEY•Ñ@ÖjU‹aTbŠb#\np\\RsS‘@\rFç²ŒŠhàÒ³q@šM=†M&ÚC3ñå^Ð7Ì+¬Ñî¹UÍrz™Ù$$u«º}ÙR¬ÔW].wcÓÃIò+µù¡Îâ¹±$·.Îñó\ZÚ†ì\\¦ÝÜÕ«{8Ý°ê9¬±Ûî†ÚiÖm\Z¨àwæ¯C¤[+åAúçšÐ¶Ñ-d‘ƒíWÆž°à)ÍUÙ¤«6¹Jöv«o†BÀ{œÕ™åùzÓäÂ&jæµÝQ ·)Þs´°þ\ZQ‹œ¹NiÍB.LÊ×¯…Ýß–‡)SYc¥ 9¤cé^´b£Tx³“œœ˜\Z`ûÔÒØ¤ÝÍ11‘P²sÒ¥Œæ*àdSÅŒc¥ŠÐ*9¤Àâ€3îˆŠlß1É¢ÍxÛŠÕd8ÿ\0;œS.ÑšxÀZŒ6iH8 1¦g4í¦“z\0$VâÓnf„l3Ò¥²³?~AÏaé\\õ+Æ°¦ädk/äÇ:‚U~Vö¬ØËÂA+Ñ¡²IÐDèr\r¤W9{áù,®Þås”oQ^o?sÐ¤¯î”lîðÁÔóÜWMk{\"¹æÑeOš>*Ha¼‰±°š9“:hí­µ¿W´ú\rÀ×ÝŸùdÂµ-¬.¦ÆìG0ù[4î¯Úv)}ÍgËköˆ¯-vå…¹˜}TƒZÐX­´EP2M_ðuŸÛZûUuýÜŸ¸„ÕGSøŸåE)>tÑt•7sÌV”×¡ë~·¸-%ž ›®\0ùðí\\.£¦^é’lºvnª~†½ˆÍKcÆi¢‹c4 \n@¹ äShð+œŠ­9«È¦\"¤¹ ‘ø«3­Q”\ZC*ÈÄµ’\r†i‰1NE,jªõ«°qLE¸£È©|¼U«>æð…‚<ú±è+­Ó|/Q	§>cg*%V+mF ÙÉÚh×WÌ!T?Äk¦±ðÔ6ÅWf\\ÿ\0å]]…œq¹\n€\08¦]#,èÊ9\r\\òœ¤j¢‘Vm2ÞÊÁŸn[×-ufQ‘~ðÎ>µÙß4—¯\Z¨åzšÈ†ØGÌãq\n1šÂ¬M\"Ê–v›Q˜sVo¬Eí«&ÁæÄ7\'©Å[µ„ªo¼Æ­ ?›çšÉÅXÒ2jWG*–jé9¥KPóFá[/jÓÜ´–±æ69ÛÜSÅ»)Ã¦¡…™è©¦ŠqÃ\"úU¨àÏAŠºªˆœP\\Ý¤qœ¶:j¬.c]•ÝáÓ-Û÷³œ9Â½ë½Óm#²Óa¶v¬h\0çz\\S.£.­©+C|&þÑè=ëÓ!’9áIc`Èã JÖ‚Öç*[$A°4˜?J¯y§¤¨c’%‘åXd\Z¼Sæ÷©	pHÍuÄãgžê^µ™™ìÜÛ9þ2Ÿâ+’Ô¼;¨é€´Ð–þzGó/ÿ\0Z½¥¢ˆ#ŸZªö{ò1ŠÕUkr\\á<\n]ø¯PÕüezÒ1ßßŒc?QÒ¼ÿ\0XÐo4—\"d-?,Š8?áZÆj[âÑ˜Ò*§m Ó‚T\"„ÑñE[•¬25Ï×E¢èRÝ]F%AäŽàUÝ#Ã­n\"žå>w`B‘ÐWU£[¯Û¥“y¿Jç[»DÒ1¶¬ÑƒMKha†%\nªrp:ÖÊB¾ÜqNòÆÅK—’°îEl›wR\\Åœ•\\“S/Mž=¨9Ól\näž¥GÒ¨c|q/`8­[ÄÙ§¿`£pü+\"À=ÄP¬x,Ã$öÞ°©¹q.@…ßËEÜÔš¦—-Å—àä‘ÜÖÕ¬vÑå~fa’Ø©¶ƒž8¡SºÔjvg?¢ÚN hææU<ÿ\0O4°1œd}ÜðkBHÙf‹†_Ö¦Ò[Rì¿0ãµG³²±~ÑÞç”ë:íÔZÄZz¤GÔâ½JßK³´·P±€@äžMrZ>‹³â“¬ME·Ê¹y»Â»‰ÀAæ•(¦®]jœÍ$s:ÞˆÚ±3òŒ(§hÒK¥ªé÷\\/ð1íí]<cæaÉè=*\rGNKØ¸áÇCWìš÷–æ^Òþë3Ö…AæŒñš¥epA6·\'\0Ÿâ¢˜ójÖæm\'#ÔSJ|Ù©Xþ4qZFÑ+uRëN†â&ŽHÕãa‚¬2\rh\ZkŒQÊ’ø“ÁYLn4äi!\'&!É_§¨®M¡*ÊC#‘_AI’1Ð×ŸxóÃÀÛjÛFÇÄêÞÞü+XMìÈ”{g?\0ÑI;d\Z+BOb¼P×c\n\0À¦hàyî}ñV?2ù—û¼\n¡m?ÙµY ÞÍyéêt_aJx5\0›,ª;ŒÕœt­î@å_ÝóQÆrÄzjSÀÅTvÈ	8Í&.ãIÑ­›wÌ¹È¢Ú5ŠÞ8¢ŒA‚\0þt	<éƒÆã½ZRŠ›¸â³²–¥lHŽ@\\“ÈêMJ.A9=ê³Ldp7`Ósû÷ú\n´ÄÑsä#“®NÖz|ž_,ÿ\0*ýMiÇóUmFÝn<…aÒUÏçJ¢¼B.Ì4»°´·E=2{“Ö´ˆÃ0éN>”Ö=*b’ÐmßQá³JMGÒ›¸ù˜ÏlÖ¤uï\0<¤‹Ê¸ê\r:•J‡ ¸&¦º©=é0CdTr«Ü«éaì1éH¦™§\'œž3R(«Nä‹Hii)€‰Ê\Z¯<)<\r¨ea‚qVŒŠjŒ‡˜âÍ!´=fkQŸ)¾xªŸðéEzÄûCC7‘.g´ùøîÇõü(­ã+£6¬l])ŠóxèÂ¹©Å¯JížH\"Š+‚{QÔëídY#VÎ*úà‘š(­âdÆ\\L±ÉÖ©Í!•r‹žh¢¡±¡ÖÈþj‚8ÜEYdaÉ9ØfŠ)% ï¨çm·áNqM?ññþð¢Š¤ñðqO˜dÆ}\Z(«–Äõ,õæ˜ü\0­VhlPi£ýgáElHŒF0	ÁÅ?vB{ÑEHÙ\n q‘É«<ŒæŠ* &©­ÍU1\rCó‘NAó·½Pk¨–X™C#=Á¢Š*š?ÿÙ', 'image/jpeg', '5153', '2025-11-09 08:07:32');

-- Table: coordinators
DROP TABLE IF EXISTS `coordinators`;
CREATE TABLE `coordinators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_display_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('1', 'Dr. Armi Grace B. DesingaÃ±o', 'Campus Administrator', NULL, NULL, NULL, '1', '2025-11-08 10:11:48', '2025-11-08 10:46:06');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('2', 'Carlo P. Malabanan, MIT', 'Extension Services Campus Coordinator', NULL, NULL, NULL, '2', '2025-11-08 10:11:48', '2025-11-08 10:47:56');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('3', 'Elarcie Balmoso', 'Department of Biology and Physical Science', NULL, NULL, NULL, '3', '2025-11-08 10:11:48', '2025-11-08 10:53:38');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('4', 'Jenny Danica P. Abayari', 'Department of Physical Education', NULL, NULL, NULL, '4', '2025-11-08 10:11:48', '2025-11-08 10:47:57');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('5', 'Rhoel Joseph R. Sarino, MIT', 'Department of Computer Science', NULL, NULL, NULL, '5', '2025-11-08 10:11:48', '2025-11-08 10:53:38');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('6', 'Elvira P. Pakingan', 'Department of Entrepreneurship', NULL, NULL, NULL, '6', '2025-11-08 10:11:48', '2025-11-08 10:53:38');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('7', 'Jose Rainer G. Penales', 'Department of Social Sciences and Humanities', NULL, NULL, NULL, '11', '2025-11-08 10:11:48', '2025-11-08 10:55:51');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('8', 'Anabella C. Gomez', 'Teacher Education Department', NULL, NULL, NULL, '12', '2025-11-08 10:11:48', '2025-11-08 10:55:51');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('9', 'Rhoniel A. Dagcasin', 'Department of Hospitality Management', NULL, NULL, NULL, '7', '2025-11-08 10:11:48', '2025-11-08 10:53:38');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('10', 'Abigail C. Gomez', 'Department of Languages Mass Communication', NULL, NULL, NULL, '8', '2025-11-08 10:11:48', '2025-11-08 10:53:38');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('11', 'Dr. Rosario B. Gumban', 'Department of Management', NULL, NULL, NULL, '9', '2025-11-08 10:54:09', '2025-11-08 10:55:51');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('12', 'Lexver G. Ocampo', 'Department of Office Administration', NULL, NULL, NULL, '10', '2025-11-08 10:54:56', '2025-11-08 10:55:51');
INSERT INTO `coordinators` (`id`, `name`, `department`, `email`, `phone`, `office_location`, `display_order`, `created_at`, `updated_at`) VALUES ('13', 'Ma. Carlota G. Baguion', 'Teacher Education Department', NULL, NULL, NULL, '13', '2025-11-08 10:56:16', '2025-11-08 10:56:16');

-- Table: departments
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `department_id` int NOT NULL AUTO_INCREMENT,
  `department_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('1', 'Department of Biological and Physical Sciences');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('2', 'Department of Computer Studies');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('3', 'Department of Hospitality Management');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('4', 'Department of Languages and Mass Communication');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('5', 'Department of Management');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('6', 'Department of Physical Education');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('7', 'Department of Social Sciences and Humanities');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('8', 'Teacher Education Department');
INSERT INTO `departments` (`department_id`, `department_name`) VALUES ('9', 'Department of Language and Mass Communication');

-- Table: detailed_evaluations
DROP TABLE IF EXISTS `detailed_evaluations`;
CREATE TABLE `detailed_evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `evaluation_id` int DEFAULT NULL,
  `eval_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `reviewed` tinyint(1) DEFAULT '0' COMMENT 'Whether evaluation has been reviewed by admin',
  `admin_suggestion` text COLLATE utf8mb4_general_ci COMMENT 'Admin suggestion for improvement',
  `admin_suggestion_date` datetime DEFAULT NULL COMMENT 'Date when admin suggestion was added',
  PRIMARY KEY (`id`),
  KEY `idx_reviewed` (`reviewed`),
  KEY `idx_eval_date` (`eval_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: document_uploads
DROP TABLE IF EXISTS `document_uploads`;
CREATE TABLE `document_uploads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL,
  `faculty_id` int NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_blob` longblob,
  `upload_date` date DEFAULT (curdate()),
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_faculty` (`faculty_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `document_uploads_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_uploads_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: enrollments
DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `program_id` int NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `enrollment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`user_id`,`program_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: evaluations
DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE `evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int NOT NULL,
  `score` int NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `eval_date` date DEFAULT (curdate()),
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_date` (`eval_date`),
  CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evaluations_chk_1` CHECK (((`score` >= 1) and (`score` <= 5)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: faculty
DROP TABLE IF EXISTS `faculty`;
CREATE TABLE `faculty` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `faculty_ibfk_1` (`user_id`),
  CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `faculty` (`id`, `user_id`, `department`, `position`, `created_at`) VALUES ('1', '2', 'Computer Science', 'Professor', '2025-09-18 09:41:35');
INSERT INTO `faculty` (`id`, `user_id`, `department`, `position`, `created_at`) VALUES ('2', '3', 'Engineering', 'Associate Professor', '2025-09-18 09:41:35');
INSERT INTO `faculty` (`id`, `user_id`, `department`, `position`, `created_at`) VALUES ('3', '1', 'Information Technology', 'Admin', '2025-10-25 08:40:24');
INSERT INTO `faculty` (`id`, `user_id`, `department`, `position`, `created_at`) VALUES ('4', '28', 'Teacher Education Department', 'Professor', '2025-11-10 09:48:31');

-- Table: images
DROP TABLE IF EXISTS `images`;
CREATE TABLE `images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL,
  `image_name` longblob,
  `image_desc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`image_id`),
  KEY `fk_program_images_programs` (`program_id`),
  KEY `idx_images_program_id` (`program_id`),
  CONSTRAINT `fk_program_images_programs` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: images_archive
DROP TABLE IF EXISTS `images_archive`;
CREATE TABLE `images_archive` (
  `archive_image_id` int NOT NULL AUTO_INCREMENT,
  `archive_program_id` int NOT NULL,
  `image_data` longblob,
  `image_desc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`archive_image_id`),
  KEY `idx_archive_program_id` (`archive_program_id`),
  KEY `idx_images_archive_program_id` (`archive_program_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `audience` enum('all','admin','faculty','student') COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_audience` (`audience`),
  KEY `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: participants
DROP TABLE IF EXISTS `participants`;
CREATE TABLE `participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `idx_participants_program_id` (`program_id`),
  KEY `idx_participants_user_id` (`user_id`),
  CONSTRAINT `participants_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: program_sessions
DROP TABLE IF EXISTS `program_sessions`;
CREATE TABLE `program_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL,
  `session_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_date` date NOT NULL,
  `session_start` time NOT NULL,
  `session_end` time NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_date` (`session_date`),
  CONSTRAINT `program_sessions_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: programs
DROP TABLE IF EXISTS `programs`;
CREATE TABLE `programs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `project_titles` longtext COLLATE utf8mb4_general_ci,
  `department_id` int NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','ongoing','ended','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'planning',
  `max_students` int DEFAULT '0',
  `description` text COLLATE utf8mb4_general_ci,
  `sdg_goals` text COLLATE utf8mb4_general_ci,
  `faculty_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `archived_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_faculty_id_faculty` (`faculty_id`),
  KEY `programs_ibfk_1` (`department_id`),
  KEY `idx_programs_is_archived` (`is_archived`),
  KEY `idx_programs_department_id` (`department_id`),
  KEY `idx_programs_faculty_id` (`faculty_id`),
  KEY `idx_programs_status` (`status`),
  CONSTRAINT `fk_faculty_id_faculty` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: programs_archive
DROP TABLE IF EXISTS `programs_archive`;
CREATE TABLE `programs_archive` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `project_titles` longtext COLLATE utf8mb4_general_ci,
  `department_id` int NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','ongoing','ended','completed','cancelled') COLLATE utf8mb4_general_ci DEFAULT 'planning',
  `max_students` int DEFAULT '0',
  `description` text COLLATE utf8mb4_general_ci,
  `sdg_goals` text COLLATE utf8mb4_general_ci,
  `faculty_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `original_program_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_faculty_id_faculty_archive` (`faculty_id`),
  KEY `department_id` (`department_id`),
  KEY `idx_programs_archive_original_id` (`original_program_id`),
  CONSTRAINT `fk_faculty_id_faculty_archive` FOREIGN KEY (`faculty_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `programs_archive_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=63 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: project_objectives
DROP TABLE IF EXISTS `project_objectives`;
CREATE TABLE `project_objectives` (
  `id` int NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `objective_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objective_description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','in_progress','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `project_objectives_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: qr_codes
DROP TABLE IF EXISTS `qr_codes`;
CREATE TABLE `qr_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `program_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_user` (`user_id`),
  KEY `idx_program` (`program_id`),
  CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qr_codes_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: qr_sessions
DROP TABLE IF EXISTS `qr_sessions`;
CREATE TABLE `qr_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program_id` int NOT NULL,
  `date` date NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_program` (`program_id`),
  KEY `idx_date` (`date`),
  KEY `idx_token` (`token`),
  CONSTRAINT `qr_sessions_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: session_participants
DROP TABLE IF EXISTS `session_participants`;
CREATE TABLE `session_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('registered','attended','absent') COLLATE utf8mb4_unicode_ci DEFAULT 'registered',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant` (`session_id`,`user_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `session_participants_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `program_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: student_profiles
DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_email` (`contact_email`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: students
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_student_id` (`student_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `students` (`id`, `user_id`, `student_id`, `course`, `contact_no`, `emergency_contact`, `created_at`) VALUES ('1', '24', '202210801', 'BSIT', '09566906517', '09566906517', '2025-11-10 09:19:43');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int NOT NULL,
  `role` enum('admin','faculty','student') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('1', 'vince', 'datu', 'vdatu218@gmail.com', '$2y$10$O.qlqiD7rnrzIjSg11AUWO5M3YOKSDpvkcnk1uxaVDL9N267cJiU2', '2', 'admin', '2025-10-05 12:03:41');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('2', 'michi', 'takanashi', 'michi@gmail.com', '$2y$10$yG/NyWYUKqX/POLYOvrA0OoqIkp4vGsdCf6ttYsKmMBS.ZUeWaQg.', '4', 'faculty', '2025-10-05 12:03:41');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('3', 'john', 'doe', 'jd@gmail.com', '1234', '5', 'faculty', '2025-10-05 12:03:41');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('4', 'sara', 'discaya', 'discayakurakot@gmail.com', '$2y$10$gdLcOWx9MQHZq7QPZe8mvutlXLib1VbceuORPv4BTBDw/wDAVj8mm', '2', 'faculty', '2025-10-06 08:42:03');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('5', 'Test', 'User', 'test.user@example.test', '$2y$10$2NyekOfuJbGtM.YKSJoW.usIQWncVCeIE6aY/es4y05f.x5o53hK6', '1', 'student', '2025-10-18 09:49:07');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('6', 'Form', 'Test', 'form.test@example.test', '$2y$10$RKeF5lqqY7QClyRP8an9o.7fQr5Vw0AryCErnBiNfwQnpjyuDdr/G', '3', 'student', '2025-10-18 09:49:40');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('7', 'Francine', 'Ciasico', 'ruri@gmail.com', '$2y$10$ksXPxYm.q3B71QDj4empgeREbIEC.Z62bxwulg/xR13dcZITyxC92', '6', 'student', '2025-10-18 09:52:33');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('8', 'Admin', 'User', 'admin@cvsu.edu.ph', '$2y$10$pybQ6h6N1jPLmXMjb5X8bexEn0/uVbZb812NGQHfJyxWPD3ujEr8O', '1', 'admin', '2025-11-06 09:33:15');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('9', 'Juan', 'Dela Cruz', 'juan.delacruz@cvsu.edu.ph', '$2y$10$xY7hkfsWQnHKYUbv42hSn.EPQEQabcaXefF6id6h7/2lQ4wQDCo7O', '2', 'faculty', '2025-11-06 09:33:15');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('10', 'Maria', 'Santos', 'maria.santos@cvsu.edu.ph', '$2y$10$ebo8uQvqIJQ/tumnos6hIeUKZkf94IXMYvQDyHUX8GDufaBmyy/9y', '3', 'faculty', '2025-11-06 09:33:15');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('11', 'Pedro', 'Reyes', 'pedro.reyes@cvsu.edu.ph', '$2y$10$K0pKQ/ffCKl.mJTK4WhWf.PPynEKYnAFX621TXJ09UfxHEbzCg8nm', '1', 'student', '2025-11-06 09:33:15');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('12', 'Dr. Maria', 'Garcia', 'maria.garcia@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '1', 'faculty', '2025-11-09 12:05:50');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('13', 'Prof. Robert', 'Cruz', 'robert.cruz@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '2', 'faculty', '2025-11-09 12:05:50');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('14', 'Chef Ana', 'Lopez', 'ana.lopez@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '3', 'faculty', '2025-11-09 12:05:51');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('15', 'Dr. James', 'Rivera', 'james.rivera@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '4', 'faculty', '2025-11-09 12:05:51');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('16', 'Prof. Catherine', 'Mendoza', 'catherine.mendoza@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '5', 'faculty', '2025-11-09 12:05:52');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('17', 'Coach Mark', 'Torres', 'mark.torres@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '6', 'faculty', '2025-11-09 12:05:52');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('18', 'Dr. Elizabeth', 'Ramos', 'elizabeth.ramos@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '7', 'faculty', '2025-11-09 12:05:53');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('19', 'Prof. Andrew', 'Santiago', 'andrew.santiago@cvsu.edu.ph', '$2y$10$pz.0NAkgTIWtkXJKyu349u2e.VCHv5DXIw7BWAT5Rcwe/r4Ae7qke', '8', 'faculty', '2025-11-09 12:05:53');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('22', 'Test', 'User', 'testuser1762765341@example.com', '$2y$12$R/kjmPNsN4WlicuIb6bS/.7Bo.ixUsZj3Wpc.ZWvbmTwbeCjJPKqq', '1', 'student', '2025-11-10 09:02:22');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('24', 'Nicole', 'Defensor', 'Nicole@gmail.com', '$2y$10$J8zagjMERlM.ze.6Wzz2De/pP8zGccN1a4uKtJ86CpzF.VJMIwe6a', '1', 'student', '2025-11-10 09:19:09');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('25', 'Thea', 'Dela Cruz', 'Thea@gmail.com', '$2y$10$dIkZ2bUGUDchHEz2QDPC/utfwnYYrEW2QD7OUvHd0OwDvccJDyowi', '1', 'faculty', '2025-11-10 09:20:23');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('26', 'Dave', 'Waay', 'waay@gmail.com', '$2y$10$3EU1Fb86FSFj7xfwFHy8ZufsokrJ1BBjYvMLV5U08vmeWtKUU586q', '1', 'faculty', '2025-11-10 09:34:02');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('27', 'Gerwin', 'Alcober', 'gerwin@gmail.com', '$2y$10$2kWcog2Viy3C11LiYjyPEe1CZ1ABur48g2krYxovkktBt5McjZKvO', '1', 'faculty', '2025-11-10 09:40:40');
INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `department_id`, `role`, `created_at`) VALUES ('28', 'Matt', 'Quiling', 'matt@gmail.com', '$2y$10$yTc0FiKamWsOyCuryXQ2Luvj5afe9/V9jSRR1DkEhnMw4pGVgDD6e', '8', 'faculty', '2025-11-10 09:48:18');

SET FOREIGN_KEY_CHECKS=1;
