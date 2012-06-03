<?php 



define ('CONTEXT_PROJECTGROUP',55);




/**
 * Course context class
 * @author Rasmus Prentow, Mikael Midtgaard, Alex Bondo Andersen, Kim Jacobsen.
 * @package mymmoodle
 */
class context_projectgroup extends context {
    private $projectGroupId;
    
    /**
     * Please use context_course::instance($projectgroupid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_PROJECTGROUP) {
            throw new coding_exception('Invalid $record->contextlevel in context_projectgroup constructor.');
        }
        $this->projectGroupId = $record->instanceid;
    }

    /**
     * Returns human readable context level name.
     *
     * @static
     * @return string the human readable context level name.
     */
    protected static function get_level_name() {
        return get_string('projectgroup');
    }
    
    public function getProjectGroupId(){
        return $this->projectGroupId;
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with Course
     * @param boolean $short whether to use the short name of the thing.
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false) {
        global $DB;

        $name = '';
      
            if ($projectgroup = $DB->get_record('projectgroup', array('id'=>$this->_instanceid))) {
                if ($withprefix){
                    $name = get_string('projectgroup','local_projectgroup').': ';
                }
                if ($short){
                    $name .= format_string($projectgroup->shortname, true, array('context' => $this));
                } else {
                    $name .= format_string($projectgroup->fullname);
               }
            }
        
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
       return new moodle_url('/local/projectgroup/index.php', array('id'=>$this->projectGroupId));
       
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @return array
     */
    public function get_capabilities() {
        global $DB;

        $sort = 'ORDER BY contextlevel,component,name';   // To group them sensibly for display

        $params = array();
        $sql = "SELECT *
                  FROM {capabilities}
                 WHERE contextlevel IN (".CONTEXT_PROJECTGROUP.",".CONTEXT_MODULE.",".CONTEXT_BLOCK.")";

        return $DB->get_records_sql($sql.' '.$sort, $params);
    }

    /**
     * Returns projectgorup context instance.
     *
     * @static
     * @param int $instanceid
     * @param int $strictness
     * @return context_course context instance
     */
    public static function instance($instanceid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_PROJECTGROUP, $instanceid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', array('contextlevel'=>CONTEXT_PROJECTGROUP, 'instanceid'=>$instanceid))) {
            if ($projectgroup = $DB->get_record('projectgroup', array('id'=>$instanceid), 'id', $strictness)) {
               
                    $record = context::insert_context_record(CONTEXT_PROJECTGROUP, $projectgroup->id, '/'.SYSCONTEXTID, 0);
                
            }
        }

        if ($record) {
            $context = new context_projectgroup($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Create missing context instances at course context level
     * @static
     *
    protected static function create_level_instances() {
        global $DB;

        $sql = "INSERT INTO {context} (contextlevel, instanceid)
                SELECT ".CONTEXT_PROJECTGROUP.", c.id
                  FROM {projectgroup} c
                 WHERE NOT EXISTS (SELECT 'x'
                                     FROM {context} cx
                                    WHERE c.id = cx.instanceid AND cx.contextlevel=".CONTEXT_PROJECTGROUP.")";
        $DB->execute($sql);
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @static
     * @return string cleanup SQL
     *
    protected static function get_cleanup_sql() {
        $sql = "
                  SELECT c.*
                    FROM {context} c
         LEFT OUTER JOIN {projectgroup} co ON c.instanceid = co.id
                   WHERE co.id IS NULL AND c.contextlevel = ".CONTEXT_PROJECTGROUP."
               ";

        return $sql;
    }

    /**
     * Rebuild context paths and depths at course context level.
     *
     * @static
     * @param $force
     *
    protected static function build_paths($force) {
        global $DB;
        throw new coding_exception("DDDDDDDDDD");
        if ($force or $DB->record_exists_select('context', "contextlevel = ".CONTEXT_PROJECTGROUP." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Standard frontpage
            $sql = "UPDATE {context}
                       SET depth = 2,
                           path = ".$DB->sql_concat("'$base/'", 'id')."
                     WHERE contextlevel = ".CONTEXT_PROJECTGROUP."
                           AND EXISTS (SELECT 'x'
                                         FROM {course} c
                                        WHERE c.id = {context}.instanceid AND c.category = 0)
                           $emptyclause";
            $DB->execute($sql);

            // standard courses
            $sql = "INSERT INTO {context_temp} (id, path, depth)
                    SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1
                      FROM {context} ctx
                      JOIN {course} c ON (c.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_PROJECTGROUP." AND c.category <> 0)
                      JOIN {context} pctx ON (pctx.instanceid = c.category AND pctx.contextlevel = ".CONTEXT_PROJECTGROUPCAT.")
                     WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                           $ctxemptyclause";
            $trans = $DB->start_delegated_transaction();
            $DB->delete_records('context_temp');
            $DB->execute($sql);
            context::merge_context_temp_table();
            $DB->delete_records('context_temp');
            $trans->allow_commit();
        } 
    } */
}

 

function get_projectgroup_context_instance( $instance = 0) 
{ 
    return context_projectgroup::instance($instance);
}

