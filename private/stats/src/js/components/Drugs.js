import _ from 'lodash'
import Moment from 'moment'

import React from 'react'
import Section from 'grommet/components/Section'
import Heading from 'grommet/components/Heading'
import Tiles from 'grommet/components/Tiles'
import Tile from 'grommet/components/Tile'
import Table from 'grommet/components/Table'
import Chart from 'grommet/components/Chart'
import Meter from 'grommet/components/Meter'

import Data from '../util/Data'

const Drugs = React.createClass({
  render() {
    if (!this.props.data) return null

    var emptyDays = _.reduce(this.props.data.drugs.perWeek, (res, value, key) => {
      res[key] = null
      return res
    }, {})

    var grouped = {}
    _.forEach(this.props.data.drugs.perWeek, (v, k) => {
      grouped[k] = Data.groupOthers(_.reduce(v, (res, v, k) => {
        _.set(res, k, v.visits)
        return res
      }, {}), 2)
    })

    return (
      <Section>
        <Heading tag='h2' align='center'>
          Drugs
        </Heading>
        <Tiles fill={true}>
          <Tile>
            <Chart series={
              _.reduce(Data.invertMulti(grouped), (res, value, key) => {
                var values = _.clone(emptyDays)
                _.merge(values, value)

                values = _.reduce(values, (res2, value2, key2) => {
                  var week = Moment(key2, 'YYYY-W')
                  res2.push([week.unix(), value2])
                  return res2
                }, [])

                res.push({
                  label: _.startCase(key),
                  values: values,
                  colorIndex: (key == 'others' ? 'graph-13' : null),
                })

                res = _.sortBy(res, (x) => {
                  var sum
                  if (x.label == 'Others') sum = 1
                  else sum = _.sumBy(x.values, (y) => {
                    return -y[1] || 0
                  })
                  return sum
                })
                return res
              }, [])
            } type='bar' smooth={true} xAxis={{
              placement: 'bottom',
              data: _.reduce(this.props.data.drugs.perWeek, (res, value, key) => {
                var week = Moment(key, 'YYYY-W')
                res.push({
                  label: week.format('[wk]W'),
                  value: week.unix(),
                })
                return res
              }, [])
            }} size='large' legend={{position: 'after', total: true}} a11yTitleId='complexBarChartTitle' a11yDescId='complexBarChartDesc' />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Table>
              <thead>
                <tr>
                  <th>Drug</th>
                  <th>Visits</th>
                  <th>Users</th>
                </tr>
              </thead>
              <tbody>
                {
                  _.reduce(Data.groupOthers(this.props.data.drugs.totals, 15, {
                    sum: (x, v) => {
                      x = x || {
                        visits: 0,
                        users: 0,
                      }
                      return {
                        visits: x.visits + v[1].visits,
                        users: x.users + v[1].users,
                      }
                    },
                    sort: (x) => {
                      return _.sumBy(x.visits)
                    },
                  }), (res, value, key) => {
                    res.push((
                      <tr key={key}>
                        <td>{_.startCase(key)}</td>
                        <td style={{textAlign:'right'}}>{value.visits}</td>
                        <td style={{textAlign:'right'}}>{value.users}</td>
                      </tr>
                    ))
                    return res
                  }, [])
                }
              </tbody>
            </Table>
          </Tile>
        </Tiles>
      </Section>
    )
  }
})

module.exports = Drugs
