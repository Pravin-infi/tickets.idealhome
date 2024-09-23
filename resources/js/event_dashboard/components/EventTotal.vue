<template>
    <div>
        <div class="table-responsive">
            <vue-good-table 
                :columns="columns" 
                :rows="rows" 
                max-height="400px"
                :fixed-header="true"
                :pagination-options="{
                    enabled: true,
                    mode: 'pages',
                }"
                :search-options="{
                    enabled: true,
                }"
                
            >
                <template slot="table-row" slot-scope="props">
                    <span  :class="[props.row.tickets ==  null ? 'fw-bold text-primary' : 'small text-muted']" >
                        {{props.formattedRow[props.column.field]}}
                    </span>
                </template>
                
            </vue-good-table>
            
        </div>
    </div>
</template>

<script>
import mixinsFilters from '../../mixins.js';
import 'vue-good-table/dist/vue-good-table.css'
import { VueGoodTable } from 'vue-good-table';

export default {

    mixins:[
        mixinsFilters
    ],
    
    props:[
        'orders',
        'event_total_route',
    ],
    components: {
        VueGoodTable,
    },
    name: 'my-component',
    data(){
        return {
            columns:[
                      {label: 'ORDER', field: 'title',},
                      {label: 'TICKETS', field: 'tickets',},
                      {label: 'TICKETS QUANTITY', field: 'tickets_quantity',},
                      {label: 'TOTAL PRICE', field: 'total_price',},
                      {label: 'TOTAL CHECKINS',  field: 'total_checkins',},
                    
                        
                      
                ],
            rows: [],
          
        }
    },

    methods:{
        eventTotal: function() {
            let url  = this.event_total_route+'?event_id='+null;
            axios.get(url).then(function(response){

            
                response.data.rows.forEach((item,index)=>{
                    this.rows.push({
                        title               : item.title,
                        tickets             : item.tickets,
                        tickets_quantity    : item.tickets_quantity,
                        total_price         : item.total_price,
                        total_checkins      : item.total_checkins,
                    });
                });    
            }.bind(this));
        },

        updateParams(newProps) {
            this.serverParams = Object.assign({}, this.serverParams, newProps);
        },
        
        onPageChange(params) {
            this.updateParams({page: params.currentPage});
            this.loadItems();
        },

        onPerPageChange(params) {
            this.updateParams({perPage: params.currentPerPage});
            this.loadItems();
        },

        onSortChange(params) {
            this.updateParams({
                sort: [{
                type: params.sortType,
                field: this.columns[params.columnIndex].field,
                }],
            });
            this.loadItems();
        },
        
        onColumnFilter(params) {
            this.updateParams(params);
            this.loadItems();
        },

        // load items is what brings back the rows from server
        loadItems() {

            this.eventTotal();
           
        }
    },
    created: function(){
        this.eventTotal();
    },
    mounted() { 

    }
}


</script>