
<?php
    if(!isset($args['entry_values'])):
?>
    <div class="alert">
        Keine Werte übergeben
    </div>
<?php
    else:
?>

        <?php
            $answers = array(
                0 => 'Stimme gar nicht zu',
                1 => 'Stimme eher nicht zu',
                2 => 'Teils/teils',
                3 => 'Stimme eher zu',
                4 => 'Stimme voll und ganz zu'
            );
            $values = $args['entry_values'];
            $total = $args['total'];
            $options = $args['options'];
            $labels = $args['labels'];
             
                      foreach ( $values as $val ) :
        ?>
        <span class="graph-label"><?= $val[5] ?></span><br>
        <div class="customgraph">
            <?php for ( $k = 0, $l = count( $val )-1; $l > $k; $k++ ) :

                  
                        

                        $percentage = ceil($val[$k] * 100 / $total );
                        if($percentage != 0):
                            
            ?>
                <div class="customgraph-brick brick-<?= $k ?>" style="width:<?= $percentage ?>%">
                    <?= $percentage ?> % 
                    
                    <div class="brick-overlay customgraph-tooltip"><?=  $answers[$k] ?></div>

                </div>


            <?php   endif;
             
                
                endfor;  
           ?>
        </div>
        <?php  endforeach;
            ?>

<?php
    endif;
?>