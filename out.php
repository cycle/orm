<?php

        file_put_contents('out.php', '<?php ' . var_export($selector->fetchData(), 1));